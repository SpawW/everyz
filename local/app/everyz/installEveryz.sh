#!/bin/bash
# Autor: Adail Horst
# Email: the.spaww@gmail.com
# Objective: Install everyz / zabbix extras

INSTALAR="N";
AUTOR="the.spaww@gmail.com";
TMP_DIR="/tmp/upgZabbix";
VERSAO_INST="3.0.0";
UPDATEBD="S";
BRANCH="master";
NOME_PLUGIN="EVERYZ";
HORARIO_BKP=$(date +"%Y_%d_%m_%H-%M");
BKP_FILE="/tmp/zeBackup$HORARIO_BKP.tgz";
# Change Log

instalaPacote() {
    registra "============== Instalando pacote(s) ($1 $2 $3 $4 $5 $6 $7 $8 $9) =================";
    $GERENCIADOR_PACOTES $PARAMETRO_INSTALL $1 $2 $3 $4 $5 $6 $7 $8 $9  ${10} \
  ${11} ${12} ${13} ${14} ${15} ${16} ${17} ${18} ${19} ${20} \
  ${21} ${22} ${23} ${24} ${25} ${26} ${27} ${28} ${29} ${30};
}

backupArquivo() {
    if [ ! -f "$BKP_FILE" ]; then
        tar -cvf "$BKP_FILE" $1;
    else
        tar -uvf "$BKP_FILE" $1;
    fi
}

registra() {
    [ -d ${TMP_DIR} ] || mkdir ${TMP_DIR}
    echo $(date)" - $1" >> $TMP_DIR/logInstall.log; 
    echo "-->Mensagem $1";
}

installMgs() {
    if [ "$1" = "U" ]; then
        tipo="Upgrade";
    else
        tipo="Clean";
    fi
    registra " $tipo install ($2)...";
}

identificaDistro() {
    registra "Finding zabbix frontend location...";
    PATHDEF=$(find / -name zabbix.php | head -n1 | sed 's/\/zabbix.php//g');
    if [ -f /etc/redhat-release -o -f /etc/system-release ]; then
#        PATHDEF="/var/www/html";
        GERENCIADOR_PACOTES='yum ';
        PARAMETRO_INSTALL=' install -y ';
        TMP=`cat /etc/redhat-release | head -n1 | tr "[:upper:]" "[:lower:]"`;
        LINUX_DISTRO=`echo $TMP | awk -F' ' '{print $1}'` ;
        LINUX_VER=`echo $TMP | awk -F' ' '{print $4}'`;
    else
        TMP=`cat  /etc/issue | head -n1 | tr "[:upper:]" "[:lower:]" | sed 's/release//g' | sed 's/  / /g' | sed 's/welcome\ to\ //g' `;
        LINUX_DISTRO=`echo $TMP | head -n1 | awk -F' ' '{print $1}'` ;
        LINUX_VER=`echo $TMP | sed 's/release//g' | awk -F' ' '{print $2}'`;
        if [ `which zypper 2>&-  | wc -l` -eq 1 ]; then
#            PATHDEF="/usr/share/zabbix";
            GERENCIADOR_PACOTES='zypper ';
            PARAMETRO_INSTALL=' install -y ';
        else
#            PATHDEF="/var/www";
            GERENCIADOR_PACOTES='apt-get ';
            PARAMETRO_INSTALL=' install -y ';
        fi
    fi

    if [ -f /tmp/upgZabbix/logInstall.log ]; then
        TMP=`cat /tmp/upgZabbix/logInstall.log | grep "Path do frontend" | tail -n1 | awk -F[ '{print $2}' | awk -F] '{print $1}'`;
        if [ ! -z $TMP ]; then
            PATHDEF=$TMP;
        fi
    fi

    case $LINUX_DISTRO in
	"ubuntu" | "debian" | "red hat" | "red" | "centos" | "opensuse" | "opensuse" | "amazon" | "oracle" )
            CAMINHO_RCLOCAL="/etc/rc.local";
            registra "Versao do Linux - OK ($LINUX_DISTRO - $LINUX_VER)"
            ;;
	*) 
            echo "$M_ERRO_DISTRO Required: wget, unzip, dialog";
            dialog \
                --title 'Problem'        \
                --radiolist "$M_ERRO_DISTRO"  \
                0 0 0                                    \
                S   "$M_DISTRO_SIM"  on    \
                N   "$M_DISTRO_NAO"  off   \
                2> $TMP_DIR/resposta_dialog.txt
            CONTINUA=`cat $TMP_DIR/resposta_dialog.txt `;
            registra " Distribuicao nao prevista, continuar [$DOWNLOADFILES [$LINUX_DISTRO - $LINUX_VER] ";
            if [ "$CONTINUA" = "S" ]; then
                PATHDEF=$(find / -name zabbix.php | head -n1 | sed 's/\/zabbix.php//g');
                #PATHDEF="/var/www";
                GERENCIADOR_PACOTES='echo ';
                CAMINHO_RCLOCAL="/etc/rc.local";
                $LINUX_DISTRO="OUTROS";
            else
                exit 1;
            fi
            #registra "Distribucao nao prevista ($LINUX_DISTRO)... favor contactar $AUTOR"; exit 1; 
        ;;
    esac

}

# Pre-requisitos para o funcionamento do instalador ============================
preReq() {
    # Verificando e instalando o wget
    RESULT=`which wget 2>&-  | wc -l`;
    STATUSPR="OK";
    if [ "$RESULT" -eq 0 ]; then
        registra "Installing wget";
        instalaPacote "wget";
        STATUSPR="Changed";
    fi
    # Verificando e instalando o dialog
    if [ `which dialog 2>&-  | wc -l` -eq 0 ]; then
        registra "Installing dialog";
        instalaPacote "dialog";
        STATUSPR="Changed";
    fi
    # Verificando e instalando o unzip
    if [ `which unzip 2>&-  | wc -l` -eq 0 ]; then
        registra "Installing unzip";
        instalaPacote "unzip";
        STATUSPR="Changed";
    fi
    # Verificando e instalando o php-curl
    if [ `which unzip 2>&-  | wc -l` -eq 0 ]; then
        registra "Installing php-curl";
        instalaPacote "php-curl php5-curl";
        STATUSPR="Changed";
    fi
    registra "Pre-req verification - $STATUSPR";
}

idioma() {
    # Selecao de Idioma -------------------------------------------------------------------------
    if [ -d $TMP_DIR ]; then
        if [ -f $TMP_DIR/resposta_dialog.txt ]; then
            rm $TMP_DIR/resposta_dialog.txt;
        fi
    else
        mkdir $TMP_DIR;
    fi
    dialog \
        --title 'Zabbix Extras Installer ['$VERSAO_INST']'        \
        --radiolist 'Informe o idioma (Enter the language for the installer) '  \
        0 0 0                                    \
        pt   'Portugues / Brasil'  on    \
        en   'English'   off   \
        2> $TMP_DIR/resposta_dialog.txt
    OPCOES=`cat $TMP_DIR/resposta_dialog.txt `;
    if [ "`echo $OPCOES| wc -m`" -eq 3 ]; then
        INSTALAR="S";
    else
        echo $OPCOES| wc -m
        registra "Instalacao abortada ($OPCOES)...";
        exit;
    fi
    case $OPCOES in
	"pt" )
      M_BASE="Este instalador ira adicionar um menu extra ao final da barra de menus do seu ambiente. Para a correta instalacao sao necessarios alguns parametros.";
      M_CAMINHO="Favor informar o caminho para o frontend do zabbix";
      M_BASE_PHP="Este instalador ira configurar a diretiva do PHP: short_open_tag, ativando-a. Este passo é necessário para instalar o ZabTree e ZabGeo.";
      M_CAMINHO_PHP="Favor informar o caminho para o arquivo php.ini";
      M_ERRO_CAMINHO_PHP="O php.ini nao foi encontrado no caminho informado.";
      M_URL="Favor informar a URL do zabbix (usando localhost)";
      M_ERRO_CAMINHO="O caminho informado para o frontend do zabbix nao foi encontrado ";
      M_ERRO_CAMINHO2="O caminho informado nao possui instalacao do frontend do zabbix";
      M_ERRO_ABORT="Instalacao abortada!";
      M_PATCH="Efetuar download dos arquivos do patch (S)?";
      M_PATCH_CAMINHO="Favor informar caminho para os arquivos do patch";
      M_PATCH_ERRO="O arquivo de patch nao foi localizado no caminho informado";
      M_INSTALL_ALL="Selecione os modulos a instalar";
      M_ZABBIX_CAT="Instalar o modulo de Gestao de Capacidade.";
      M_ZABBIX_SC="Instalar o modulo de Gestao de Armazenamento.";
      M_ZABBIX_NS="Instalar o modulo de Relatorio de itens nao suportados.";
      M_ZABBIX_EM="Instalar o modulo de Correlacionamento de eventos.";
      M_RESUMO_FRONT="Caminho do frontend: ";
      M_ERRO_FRONT="A URL informada para o frontend do Zabbix nao esta acessivel a partir deste servidor.";
      M_RESUMO_PATCH="Localizacao dos arquivos do patch: ";
      M_RESUMO_INSTALA="Confirma a instalacao nos moldes acima?";
      M_UPGRADE_BD="Foi detectada uma instalação anterior. Deseja SUBSTITUIR os dados das tabelas do ZE pelos novos ? Caso a instalação esteja danificada você deverá escolher esta opção!";
      M_UPGRADE_BD_SIM="Recriar tabelas zbxe";
      M_UPGRADE_BD_NAO="Manter tabelas zbxe existentes";
      M_DOWNLOAD_FILES_SIM="Baixar os arquivos mais atuais (recomendado)";
      M_DOWNLOAD_FILES_NAO="Utilizar os arquivos baixados e salvos manualmente em /tmp";
      M_ERRO_DISTRO="Distribucao nao prevista ($LINUX_DISTRO)... favor contactar ";
      M_DISTRO_SIM="SIM, continue mesmo sem o suporte a instalacao de pacotes (necessario wget, dialog e unzip).";
      M_DISTRO_NAO="NAO, aborte a instalacao.";
      M_DOWNLOAD_FILE="Baixar a última versão?";
      M_DOWNLOAD_SIM="SIM, baixe a última versão a partir do github (acesso a internet necessario).";
      M_DOWNLOAD_NAO="NAO, use o arquivo existente em /tmp/EveryZ.zip";
            ;;
	*) 
      M_BASE="This installer will add an extra menu to the end of the menu bar of your environment. For installation are needed to inform some parameters.";
      M_CAMINHO="Please enter the path to the zabbix frontend ";
      M_BASE_PHP="This installer will configure the PHP: short_open_tag, activating it. This step is required to install and ZabTree ZabGeo.";
      M_CAMINHO_PHP="Please enter the path to php.ini.";
      M_ERRO_CAMINHO_PHP="The php.ini file was not found in the path provided.";
      M_URL="Please enter the URL to the zabbix frontend (using localhost)";
      M_ERRO_CAMINHO="The informed path to zabbix frontend is not valid ";
      M_ERRO_CAMINHO2="The informed path dont have a valid zabbix frontend";
      M_ERRO_ABORT="Install aborted!";
      M_PATCH="Download the patch files (S) (S = Yes)?";
      M_PATCH_CAMINHO="Please inform the path to patch files";
      M_PATCH_ERRO="The patch file not found on informed path";
      M_INSTALL_ALL="Select the available menu items";
      M_ZABBIX_CAT="Install Capacity and Trends.";
      M_ZABBIX_SC="Install Storage Costs.";
      M_ZABBIX_NS="Install Not Supported Itens Report.";
      M_ZABBIX_NS="Install Event Management.";
      M_RESUMO_FRONT="Path to the Zabbix frontend: ";
      M_ERRO_FRONT="The informed URL for Zabbix frontend is not available from this server.";
      M_RESUMO_PATCH="Path to patch files: ";
      M_RESUMO_INSTALA="Confirm installation?";
      M_UPGRADE_BD="A previous installation was detected. Do you want to REPLACE the data from the tables by the new ZBXE data? If the installation is damaged you must choose this option!";
      M_UPGRADE_BD_SIM="Re-create zbxe tables";
      M_UPGRADE_BD_NAO="Preserve zbxe tables";
      M_DOWNLOAD_FILES_SIM="Get from internet latest version of patchs (recomended)";
      M_DOWNLOAD_FILES_NAO="Use files saved in /tmp";
      M_ERRO_DISTRO="Unkown linux version ($LINUX_DISTRO)... please contact for support: ";
      M_DISTRO_SIM="YES, continue without support to install OS packages (required wget, dialog and unzip) (S = YES).";
      M_DISTRO_NAO="NO, stop install.";
      M_DOWNLOAD_FILE="Download the latest version?";
      M_DOWNLOAD_SIM="YES, download the latest version from github (internet access required).";
      M_DOWNLOAD_NAO="NO, use /tmp/EveryZ.zip.";
        ;;
    esac
}

caminhoFrontend() {
    dialog --inputbox "$M_BASE\n$M_CAMINHO" 0 0 "$PATHDEF" 2> $TMP_DIR/resposta_dialog.txt;
    CAMINHO_FRONTEND=`cat $TMP_DIR/resposta_dialog.txt`;
    if [ ! -d "$CAMINHO_FRONTEND" ]; then        
        registra " $M_ERRO_CAMINHO ($CAMINHO_FRONTEND). $M_ERRO_ABORT";
        exit;
    else
        # Verificar se o arquivo zabbix.php existe no caminho informado --------
        if [ ! -f "$CAMINHO_FRONTEND/zabbix.php" ]; then
            registra " $M_ERRO_CAMINHO2 ($CAMINHO_FRONTEND). $M_ERRO_ABORT.";
            exit;
        else
            DBUSER=`cat "$CAMINHO_FRONTEND/conf/zabbix.conf.php" | `;
        fi
        registra " Path do frontend: [$CAMINHO_FRONTEND] ";
    fi
    cd $CAMINHO_FRONTEND;
}

tipoInstallZabbix(){
    case $LINUX_DISTRO in
	"ubuntu" | "debian" | "centos" | "opensuse" | "opensuse" | "amazon" | "oracle" )
            echo "ainda nao sei...";
            ;;
        "red hat" | "red" )
            rpm -qa | grep zabbix | wc -l;
            ;;
	*) 
            echo "$M_ERRO_DISTRO - I dont know how check packages in your distro... sory... you know? send-me how ;) ";
            ;;
    esac
}

instalaMenus() {
    registra "Instalando menus customizados...";
    cd $CAMINHO_FRONTEND;
    # Adiciona menu extra
    ARQUIVO="include/menu.inc.php";
    backupArquivo $ARQUIVO;
    TAG_INICIO="##$NOME_PLUGIN-Menus-custom";
    TAG_FINAL="$TAG_INICIO-FIM";
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
    if [ ! -z $INIINST ]; then
        installMgs "U" "NS"; 
        sed -i "$INIINST,$FIMINST d" $ARQUIVO;
    else
        installMgs "N" "NS"; 
        TMP="\$deny";
        INIINST=`cat $ARQUIVO | sed -ne "/$TMP/{=;q;}"`;
        INIINST=`expr $INIINST + 1`;
        FIMINST=$INIINST;
    fi
 
    TXT_CUSTOM=" if (file_exists(\"local/include/menu.inc.change.php\")) { \n include_once \"local/include/menu.inc.change.php\"; \n } ";
    sed -i "$INIINST i$TAG_INICIO\n$TXT_CUSTOM\n$TAG_FINAL" $ARQUIVO

    # Verificação de instalação prévia do patch no javascript --------------
    TAG_INICIO="'admin': 0,'extras':0";
    if [ "`cat js/main.js | grep \"$TAG_INICIO\" | wc -l`" -eq 0 ]; then
        LINHA=`cat js/main.js | sed -ne "/{'empty'\:/{=;q;}"`;
        registra "Instalando menu no javascript...";
        sed -i "104s/'admin': 0/'admin': 0,'extras':0/g" js/main.js 
    fi
    # Ajusta o popup menu para suportar a pesquisa por key_
    IDENT=", \"name\"'";
    #Zabbix 3.0.0
    sed -i "148s/$IDENT/, \"name\", \"key_\"'/" popup.php
    # Ajusta o copyright

    TAG_INICIO="##$NOME_PLUGIN-Copyright-custom";
    TAG_FINAL="$TAG_INICIO-FIM";
    if [ "`cat include/html.inc.php | grep \"$TAG_INICIO\" | wc -l`" -eq 0 ]; then
               
    # Ajuste do Copyright
        registra "Instalando Copyright...";
        IDENT="->setAttribute('target', '_blank')";
        NOVO="$IDENT\n$TAG_INICIO\n, ' | ', (new CLink('EveryZ \/ '.EVERYZ_VERSION, 'http:\/\/www.everyz.org\/'))\n\t->addClass(ZBX_STYLE_GREY)\n\t->addClass(ZBX_STYLE_LINK_ALT)\n\t->setAttribute('target', '_blank')\n$TAG_FINAL";
        sed -i "s/$IDENT/$NOVO/" include/html.inc.php
    fi
    if [ "`cat include/defines.inc.php | grep \"EVERYZ_VERSION\" | wc -l`" -eq 0 ]; then
        echo "define ('EVERYZ_VERSION','$BRANCH');" >> include/defines.inc.php;
    fi
    FIMINST=$(($FIMINST+1));
}

customLogo() {
    registra "Configurando suporte a logotipo personalizado...";
    ARQUIVO="app/views/layout.htmlpage.menu.php";
    backupArquivo $ARQUIVO;
    TAG_INICIO="##$NOME_PLUGIN-logo-obects-custom";
    TAG_FINAL="$TAG_INICIO-FIM";
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    if [ -z $INIINST ]; then
        installMgs "N" "logo"; 
    else
        installMgs "U" "logo"; 
        FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
        sed -i "$INIINST,$FIMINST d" $ARQUIVO;
    fi
# Logo do site
    TXT_CUSTOM_LOGO="\t\$logoCompany = new CDiv(SPACE, '')\;\n\t\$logoCompany->setAttribute('style', 'float: left; margin: 10px 0px 0 0; background: url(\"zbxe-logo.php\") no-repeat; height: 25px; width: 140px; cursor: pointer;');";
    TXT_CUSTOM_LOGO="$TXT_CUSTOM_LOGO\n\t\$logoZE = new CDiv(SPACE, '');\n\t\$logoZE->setAttribute('style', 'float: left; margin: 10px 0px 0 0; background: url(\"local\/app\/everyz\/images\/zbxe-logo.png\") no-repeat; height: 25px; width: 30px; cursor: pointer;');";
    TAG1="\/\/ 1st level menu";
    NOVO="$TAG1\n$TAG_INICIO\n$TXT_CUSTOM_LOGO\n$TAG_FINAL";
    sed -i "s/$TAG1/$NOVO/" $ARQUIVO
    TAG_INICIO="##$NOME_PLUGIN-logo-custom";
    TAG_FINAL="$TAG_INICIO-FIM";
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    if [ ! -z $INIINST ]; then
        FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
        sed -i "$INIINST,$FIMINST d" $ARQUIVO;
    fi
    TXT_CUSTOM1="\t(new CLink(\$logoCompany,'zabbix.php?action=dashboard.view'))\n\t->addItem(new CLink(\$logoZE,'http:\/\/www.everyz.org'))";
    TAG1="(new CLink((new CDiv())->addClass(ZBX_STYLE_LOGO), 'zabbix.php?action=dashboard.view'))";
    NOVO="#$TAG1\n$TAG_INICIO\n$TXT_CUSTOM1\n$TAG_FINAL";
    sed -i "s/$TAG1/$NOVO/" $ARQUIVO

    # Comentando classe de logo 
    TAG1="->addClass(ZBX_STYLE_HEADER_LOGO)";
    sed -i "s/$TAG1/#$TAG1/" $ARQUIVO

    FIMINST=$(($FIMINST+1));
    # ==========================================================================
    # Configuracao login screen ================================================
    # ==========================================================================
    # Objetos de suporte aos logos customizados 
    registra "Configurando suporte a logotipo personalizado na tela de login...";
    ARQUIVO="include/views/general.login.php";
    # Logotipo do login
    TXT_CUSTOM_LOGO="\t\$logoCompany = new CDiv(SPACE, '')\;\n\t\$logoCompany->setAttribute('style', 'float: left; margin: 10px 0px 0 0; background: url(\"zbxe-logo.php?mode=login\") no-repeat; height: 25px; width: 200px; cursor: pointer;');";
    TXT_CUSTOM_LOGO="$TXT_CUSTOM_LOGO\n\t\$logoZE = new CDiv(SPACE, '');\n\t\$logoZE->setAttribute('style', 'float: right; margin: 10px 0px 0 0; background: url(\"local\/app\/everyz\/images\/zbxe-logo.png\") no-repeat; height: 25px; width: 30px; cursor: pointer;');";
    backupArquivo $ARQUIVO;
    TAG_INICIO="##$NOME_PLUGIN-logo-obects-custom";
    TAG_FINAL="$TAG_INICIO-FIM";
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    if [ -z $INIINST ]; then
        installMgs "N" "logo"; 
    else
        installMgs "U" "logo"; 
        FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
        sed -i "$INIINST,$FIMINST d" $ARQUIVO;
    fi
    
    TAG1='global $ZBX_SERVER_NAME;';
    NOVO="$TAG1\n$TAG_INICIO\n$TXT_CUSTOM_LOGO\n$TAG_FINAL";
    sed -i "s/$TAG1/$NOVO/" $ARQUIVO
    # Ativacao dos logos customizados
    TAG_INICIO="##$NOME_PLUGIN-logo-custom";
    TAG_FINAL="$TAG_INICIO-FIM";
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    if [ ! -z $INIINST ]; then
        FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
        sed -i "$INIINST,$FIMINST d" $ARQUIVO;
    fi
    TXT_CUSTOM1="\t (new CDiv([(new CLink(\$logoCompany,'zabbix.php?action=dashboard.view')),\n\t (new CLink(\$logoZE,'http:\/\/www.everyz.org'))])),";
    TAG1="(new CDiv())->addClass(ZBX_STYLE_SIGNIN_LOGO),";
    NOVO="$TAG1\n$TAG_INICIO\n$TXT_CUSTOM1\n$TAG_FINAL";
    sed -i "s/$TAG1/$NOVO/" $ARQUIVO
    FIMINST=$(($FIMINST+1));
}

instalaLiteral() {
    installMgs "N" "Literal values"; 
    ARQUIVO="include/func.inc.php";
    backupArquivo $ARQUIVO;
    TAG_INICIO="\#\#$NOME_PLUGIN-Literal";
    TAG_FINAL="$TAG_INICIO-FIM";
    cd $CAMINHO_FRONTEND;
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
    if [ ! -z $INIINST ]; then
      sed -i "$INIINST,$FIMINST d" $ARQUIVO
    fi
    NUMLINHA=`cat $ARQUIVO | sed -ne "/\/\/ any other unit/{=;q;}"`;
    sed -i "$NUMLINHA i##$TAG_INICIO\n##$TAG_FINAL" $ARQUIVO
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
    sed -i "$FIMINST i if(strpos(strtolower(\$options['units']),'literal') > -1){ \$sufixo=explode('-',\$options['units']); return round(\$options['value'], ZBX_UNITS_ROUNDOFF_UPPER_LIMIT).\" \".\$sufixo[1]; }" $ARQUIVO
    FIMINST=$(($FIMINST+1));
}

corTituloMapa() {
    # Arquivo com as principais definicoes dos mapas ===========================
    ARQUIVO="include/classes/sysmaps/CMapPainter.php";
    backupArquivo $ARQUIVO;
    # Ajustando o titulo do mapa ===============================================
    TAG_INICIO="##$NOME_PLUGIN-MapTitle";
    TAG_FINAL="$TAG_INICIO-FIM";
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    if [ ! -z $INIINST ]; then
        FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
        sed -i "$INIINST,$FIMINST d" $ARQUIVO;
    fi
    # Antigo controle de titulo ================================================
    sed -i "s/\$this->canvas->drawTitle/#\$this->canvas->drawTitle/" $ARQUIVO;
    # Removendo o titulo dos mapas =============================================
    TXT_CUSTOM1="\t\$this->options['graphtheme']['textcolor'] = zbxeMapTitleColor();\n\tif (zbxeMapShowTitle()) {";
    TXT_CUSTOM1="$TXT_CUSTOM1\n\t\t\$this->canvas->drawTitle(\$this->mapData['name'], \$this->options['graphtheme']['textcolor']);\n\t}";
#    TXT_CUSTOM1="\t\$this->options['graphtheme']['textcolor'] = \$COR\;";
    TAG1="protected function paintTitle() {";
    NOVO="$TAG1\n$TAG_INICIO\n$TXT_CUSTOM1\n$TAG_FINAL";
    sed -i "s/$TAG1/$NOVO/" $ARQUIVO
    # Desabilita a borda do mapa ===============================================
# Nao achei o ponto de inclusao no zabbix 3...
#    BORDA='false';
#    sed -i "s/'border' => .*,/'border' => $BORDA,/" $ARQUIVO;
    # Define a cor de fundo do mapa ============================================
#    CORFUNDO='false';
#    sed -i "s/'bgColor' => '.*',/'bgColor' => '#$CORFUNDO',/" $ARQUIVO;

    # Arquivo com as principais definicoes dos mapas ===========================
    ARQUIVO="include/classes/sysmaps/CCanvas.php";
    backupArquivo $ARQUIVO;
    sed -i "s/\$this->width - .*, \$this->height - 12, .*\$date/\$this->width - zbxeCompanyNameSize(), \$this->height - 12, zbxeCompanyName().\$date/" $ARQUIVO;
    # Ajuste de cor no titulo dos elementos do mapa ============================
    #ToDo
    FIMINST=$(($FIMINST+1));
}

downloadPackage() {
    ARQ_TMP="$1";
    REPOS="$2";
    if [ "$DOWNLOADFILES" = "S" ]; then
        if [ -f $ARQ_TMP ]; then
            rm $ARQ_TMP;
        fi
        # Baixa repositorio
        wget $REPOS -O $ARQ_TMP --no-check-certificate;
    fi
}

unzipPackage() {
    ARQ_TMP="$1";
    DIR_TMP="$2";
    DIR_DEST="$3";
    
    cd /tmp;
    # Descompacta em TMP
    if [ -e $DIR_TMP ]; then
        rm -rf $DIR_TMP;
    fi
    unzip $ARQ_TMP > /tmp/tmp_unzip_ze.log;
    cd $DIR_TMP
    if [ ! -e "$DIR_DEST" ]; then
        mkdir -p "$DIR_DEST";
    fi
}


instalaGit() {
    REPOS="https://github.com/SpawW/everyz/archive/$BRANCH.zip";
    ARQ_TMP_BD="/tmp/pluginExtrasBD.htm";
    ARQ_TMP="/tmp/EveryZ.zip";
    DIR_TMP="/tmp/everyz-$BRANCH/";

    downloadPackage "$ARQ_TMP" "$REPOS";
    unzipPackage "$ARQ_TMP" "$DIR_TMP" "$CAMINHO_FRONTEND";

    cp -Rp * "$CAMINHO_FRONTEND";

    if [ -f "$ARQ_TMP_BD" ]; then
        rm "$ARQ_TMP_BD";
    fi
}

confirmaDownload() {
    dialog \
        --title 'Download /tmp/EveryZ.zip'        \
        --radiolist "$M_DOWNLOAD_FILE"  \
        0 0 0                                    \
        S   "$M_DOWNLOAD_SIM"  on    \
        N   "$M_DOWNLOAD_NAO"  off   \
        2> $TMP_DIR/resposta_dialog.txt
    DOWNLOADFILES=`cat $TMP_DIR/resposta_dialog.txt `;
}

apacheDirectoryConf() {
    echo "<Directory \"$CAMINHO_FRONTEND/local/app/everyz/$1\"> 
 Options FollowSymLinks 
 AllowOverride All 
 Require all granted 
 Order allow,deny
 Allow from all
</Directory>" >> $APACHEROOT/everyz.conf;
}
configuraApache() {
    # Localizar onde estão os arquivos de configuração do apache
    APACHEROOT=$(apachectl -V 2> /dev/null | grep HTTPD | awk -F= '{print $2}' | sed 's/"//g' );
    [[ -d "$APACHEROOT/conf.d" ]] && APACHEROOT=$APACHEROOT"/conf.d"; || APACHEROOT=$APACHEROOT"/conf-enabled";
    # Adicionar o arquivo de configuração do everyz
    BASECONF="# Allow to read images, scripts, css files on EveryZ installation ";
    echo "$BASEZCONF" > "$APACHEROOT/everyz.conf";
    apacheDirectoryConf "js";
    apacheDirectoryConf "images";
    apacheDirectoryConf "css";
}

####### Parametros de instalacao -----------------------------------------------

if [ $(alias  | grep rm | wc -l ) == "1" ]; then
    echo "Removendo alias do rm...";
    unalias rm;
fi

# Idenfificando distribuicao
identificaDistro;
preReq;
idioma;
tipoInstallZabbix;
caminhoFrontend;
confirmaDownload;
####### Download de arquivos ---------------------------------------------------

####### Instalacao -------------------------------------------------------------
configuraApache;
instalaGit;
instalaMenus;
customLogo;
instalaLiteral;
corTituloMapa;

echo "Installed";
echo "You need to restart your apache server!";