#!/bin/bash
# Autor: Adail Horst
# Email: everyz@everyz.org
# Objective: Install Everyz / Zabbix Extras 
# ZABBIX_VERSIONS: 3.0.*, 3.2.* and 3.4.* - Tested with 3.0.9, 3.2.5 and 3.4.0
# 20170918 - Update for change popup.php and validation of files for multiple zabbix versions

INSTALAR="N";
AUTOR="the.spaww@gmail.com"; 
TMP_DIR="/tmp/upgZabbix";
VERSAO_INST="1.1.3";
VERSAO_EZ="1.1.3";
UPDATEBD="S";
BRANCH="master";
NOME_PLUGIN="EVERYZ";
HORARIO_BKP=$(date +"%Y_%d_%m_%H-%M");
BKP_FILE="/tmp/zeBackup$HORARIO_BKP.tgz";

paramValue() {
    echo $(echo $1 | awk -F'=' '{print $2}' );
}

# Parametros de configuração para automatização ================================
if [ $# -gt 0 ]; then
    for i in "$@"
    do
        case $i in
            -a=*|--apache=*)
                RECONFAPACHE=$(paramValue $i);
                if [ "$RECONFAPACHE" != 'S' ] && [ "$RECONFAPACHE" != 'N' ]; then
                    echo "Invalid apache option: $RECONFAPACHE";
                else
                    echo "Apache option selected: $RECONFAPACHE";
                fi
                shift # past argument=value
            ;;
            -f=*|--frontend-path=*)
                CAMINHO_FRONTEND=$(paramValue $i);
                if [ ! -d "$CAMINHO_FRONTEND"  ]; then
                    echo "Invalid frontend path: $CAMINHO_FRONTEND";
                else
                    echo "Frontend selected path: $CAMINHO_FRONTEND";
                fi
                shift # past argument=value
            ;;
            -d=*|--download=*)
                DOWNLOADFILES=$(paramValue $i);
                if [ "$DOWNLOADFILES" != 'S' ] && [ "$DOWNLOADFILES" != 'N' ]; then
                    echo "Invalid download option: $DOWNLOADFILES";
                else
                    echo "Download option selected: $DOWNLOADFILES";
                fi
                shift # past argument=value
            ;;
            -l=*|--language=*)
                PAR_IDIOMA=$(paramValue $i);
                if [ "$PAR_IDIOMA" != 'pt' ] && [ "$PAR_IDIOMA" != 'en' ]; then
                    echo "Invalid language: $PAR_IDIOMA";
                else
                    echo "Language selected: $PAR_IDIOMA";
                fi
                shift # past argument=value
            ;;
            -i=*|--distro=*)
                LINUX_DISTRO=$(paramValue $i  | tr '[:upper:]' '[:lower:]' );
                case $LINUX_DISTRO in
                    "ubuntu" | "debian")
                        if [ `which apt-get 2>&-  | wc -l` -eq 0 ]; then
                            registra "APT-GET command not found ($LINUX_DISTRO)";
                            exit
                        fi
                    ;;
                    "redhat" | "red" | "centos"  | "oracle" | "amazon" )
                        if [ `which yum 2>&-  | wc -l` -eq 0 ]; then
                            registra "YUM command not found ($LINUX_DISTRO)";
                            exit
                        fi
                    ;;
                    "opensuse" )
                        if [ `which zipper 2>&-  | wc -l` -eq 0 ]; then
                            registra "ZIPPER command not found ($LINUX_DISTRO)";
                            exit
                        fi
                    ;;
                    "freebsd" )
                        if [ `which pkg 2>&-  | wc -l` -eq 0 ]; then
                            registra "PKG command not found ($LINUX_DISTRO)";
                            exit
                        fi
                    ;;
                    *) 
                        echo "Invalid DISTRO option: $LINUX_DISTRO";
                        exit;
                    ;;
                esac
                echo "DISTRO option: [$LINUX_DISTRO]";
                if [ `which wget 2>&-  | wc -l` -eq 0 ]; then
                    registra "GET command not found ($LINUX_DISTRO) cant install!";
                    exit
                fi
                shift # past argument=value
            ;;
            *)
                DEFAULT=YES
                echo "default";
                shift # past argument with no value
            ;;
            *)
                    # unknown option
            ;;
        esac
    done
    PARAM_ENABLED="S";
    echo "Installation using parameters! No packages are installed and absent requirements can broken the installation. ";
else
    PARAM_ENABLED="N";
fi

# Tenta carregar o frontend do Zabbix para criar as tabelas e evitar mensagens de erro
primeiroAcesso() {
    registra "Database install...";
    ARQUIVO="local/app/everyz/include/lockEverys.php";
    # Criar arquivo temporario bloqueando os updates automaticos pela web
    cd $CAMINHO_FRONTEND;
    echo "<?php define(\"EZ_STATUS\", 'U');" > $ARQUIVO;
    php $CAMINHO_FRONTEND/local/app/everyz/init/everyz.initdb.php | grep EveryZ  | wc -l
    # Criar arquivo liberando os updates automaticos pela web
    cd $CAMINHO_FRONTEND;
    rm $ARQUIVO;
}

# Parametros de configuração ===================================================

instalaPacote() {
    if [ "$PARAM_ENABLED" != "S" ]; then
    registra "============== Instalando pacote(s) ($1 $2 $3 $4 $5 $6 $7 $8 $9) =================";
    $GERENCIADOR_PACOTES $PARAMETRO_INSTALL $1 $2 $3 $4 $5 $6 $7 $8 $9  ${10} \
 ${11} ${12} ${13} ${14} ${15} ${16} ${17} ${18} ${19} ${20} \
 ${21} ${22} ${23} ${24} ${25} ${26} ${27} ${28} ${29} ${30};
    fi
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
    registra "$tipo install ($2)...";
}

identificaDistro() {
    if [ -z "$LINUX_DISTRO" ]; then
        if [ "$CAMINHO_FRONTEND" = "" ]; then
            registra "Finding zabbix frontend location...";    
            PATHDEF=$(find / -name zabbix.php | head -n1 | sed 's/\/zabbix.php//g');
        fi
        if [ -f /etc/redhat-release -o -f /etc/system-release ]; then
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
                GERENCIADOR_PACOTES='zypper ';
                PARAMETRO_INSTALL=' install -y ';
            else
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
            "ubuntu" | "debian" | "red hat" | "red" | "centos" | "opensuse" | "opensuse" | "amazon" | "oracle"  )
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
    fi
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
    #if [ `which unzip 2>&-  | wc -l` -eq 0 ]; then
    #    registra "Installing php-curl";
    #    instalaPacote "php-curl php5-curl";
    #    STATUSPR="Changed";
    #fi
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
    if [ "$PAR_IDIOMA" != "" ]; then
        OPCOES=$PAR_IDIOMA;
    else
        dialog \
            --title "EveryZ Installer [$VERSAO_INST]"        \
            --radiolist 'Informe o idioma (Enter the language for the installer) '  \
            0 0 0                                    \
            pt   'Portugues / Brasil'  on    \
            en   'English'   off   \
            2> $TMP_DIR/resposta_dialog.txt
        OPCOES=`cat $TMP_DIR/resposta_dialog.txt `;
    fi
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
      M_CONFAPACHE="Tentar configuraçao automatica do apache?";
      M_CONFAPACHE_SIM="SIM, configura e reinicia o apache.";
      M_CONFAPACHE_NAO="NAO, você terá que verificar manualmente suas configurações do apache.";
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
      M_CONFAPACHE="Try automatic configuration of apache?";
      M_CONFAPACHE_SIM="YES, add everyz.conf and restart apache.";
      M_CONFAPACHE_NAO="NO, you need to check and configure apache by your self.";
        ;;
    esac
}

caminhoFrontend() {
    if [ "$CAMINHO_FRONTEND" = "" ]; then
        dialog --inputbox "$M_BASE\n$M_CAMINHO" 0 0 "$PATHDEF" 2> $TMP_DIR/resposta_dialog.txt;
        CAMINHO_FRONTEND=`cat $TMP_DIR/resposta_dialog.txt`;
    fi
    if [ ! -d "$CAMINHO_FRONTEND" ]; then        
        registra " $M_ERRO_CAMINHO ($CAMINHO_FRONTEND). $M_ERRO_ABORT";
        exit;
    else
        # Verificar se o arquivo zabbix.php existe no caminho informado --------
        if [ ! -f "$CAMINHO_FRONTEND/zabbix.php" ]; then
            registra " $M_ERRO_CAMINHO2 ($CAMINHO_FRONTEND). $M_ERRO_ABORT.";
            exit;
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
        NOVO="$IDENT\n$TAG_INICIO\n, ' | ', (new CLink('EveryZ '.EVERYZ_VERSION, 'http:\/\/www.everyz.org\/'))\n\t->addClass(ZBX_STYLE_GREY)\n\t->addClass(ZBX_STYLE_LINK_ALT)\n\t->setAttribute('target', '_blank')\n$TAG_FINAL";
        sed -i "s/$IDENT/$NOVO/" include/html.inc.php
    fi
    if [ "`alias | grep mv= | wc -l`" -eq 1 ]; then
        unalias mv
    fi
    mv include/defines.inc.php /tmp/defines.inc.php.old
    cat /tmp/defines.inc.php.old | grep -v "EVERYZ_VERSION" > include/defines.inc.php;
    echo "define ('EVERYZ_VERSION','$VERSAO_EZ');" >> include/defines.inc.php;
    #if [ "`cat include/defines.inc.php | grep \"EVERYZ_VERSION\" | wc -l`" -eq 0 ]; then
    #cat include/defines.inc.php | grep -v "EVERYZ_VERSION" > include/defines.inc.php;
    #fi
    FIMINST=$(($FIMINST+1));
}

customLogo() {
    registra "Configurando suporte aos scripts e estilos do EveryZ...";

    # Especialmente para o  dashboard do Zabbix
    ARQUIVO="app/views/monitoring.dashboard.view.php";
    TAG_INICIO="<!--$NOME_PLUGIN-dashsearch-->";
    TAG_FINAL="<!--$NOME_PLUGIN-dashsearch-FIM-->";
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    if [ ! -z $INIINST ]; then
        FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
        sed -i "$INIINST,$FIMINST d" $ARQUIVO;
    fi
    TXT_CUSTOM1="<link href=\"local\/app\/everyz\/css\/everyz.css\" rel=\"stylesheet\" type=\"text\/css\" id=\"skinSheet\">";
    TXT_CUSTOM1="$TXT_CUSTOM1\n<script src=\"local\/app\/everyz\/js\/everyzFunctions.js\" type=\"text\/javascript\"><\/script>";
    TAG1="?>";
    NOVO="$TAG1\n$TAG_INICIO\n$TXT_CUSTOM1\n$TAG_FINAL";
    sed -i "s/$TAG1/$NOVO/" $ARQUIVO

    # Todas as demais páginas...
    ARQUIVO="include/page_footer.php";
    TAG_INICIO="##$NOME_PLUGIN-footer";
    TAG_FINAL="$TAG_INICIO-FIM";
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    if [ ! -z $INIINST ]; then
        FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
        sed -i "$INIINST,$FIMINST d" $ARQUIVO;
    fi
    TXT_CUSTOM1="zbxeEveryZGlobal();";
    TAG1="echo '<\/body><\/html>';";
    NOVO="\n$TAG_INICIO\n\t$TXT_CUSTOM1\n$TAG_FINAL\n\t$TAG1";
    sed -i "s/$TAG1/$NOVO/" $ARQUIVO

    registra "Configurando suporte a logotipo personalizado...";
    ARQUIVO="app/views/layout.htmlpage.menu.php";
    backupArquivo $ARQUIVO;

    TAG_INICIO="##$NOME_PLUGIN-logo-custom";
    TAG_FINAL="$TAG_INICIO-FIM";
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    if [ ! -z $INIINST ]; then
        FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
        sed -i "$INIINST,$FIMINST d" $ARQUIVO;
    fi
    TXT_CUSTOM1="\t (zbxeCustomMenu())";
    TAG1="(new CLink((new CDiv())->addClass(ZBX_STYLE_LOGO), 'zabbix.php?action=dashboard.view'))";
    NOVO="#$TAG1\n$TAG_INICIO\n$TXT_CUSTOM1\n$TAG_FINAL";
    sed -i "s/$TAG1/$NOVO/" $ARQUIVO

    # Comentando classe de logo 
    TAG1="->addClass(ZBX_STYLE_HEADER_LOGO)";
    sed -i "s/$TAG1/#$TAG1/" $ARQUIVO

    # Customizacao do global search ============================================
    registra "Configurando suporte a global search personalizado...";
    TAG_INICIO="##$NOME_PLUGIN-custom-search-share";
    TAG_FINAL="$TAG_INICIO-FIM";
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    if [ ! -z $INIINST ]; then
        FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
        sed -i "$INIINST,$FIMINST d" $ARQUIVO;
    fi
#    Share
    TAG1="(new CLink('Share'";
    LINHA=`cat $ARQUIVO | sed -ne "/$TAG1/{=;q;}"`;
    sed -i "s/$TAG1/#$TAG1/g" $ARQUIVO
    TAG1="(new CLink('', ''))->setAttribute('onclick','zbxeSearch(\"share\");')";
    sed -i "$LINHA i\\$TAG_INICIO\n$TAG1\n$TAG_FINAL" $ARQUIVO;
#    Documentation
    TAG_INICIO="##$NOME_PLUGIN-custom-search-doc";
    TAG_FINAL="$TAG_INICIO-FIM";
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    if [ ! -z $INIINST ]; then
        FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
        sed -i "$INIINST,$FIMINST d" $ARQUIVO;
    fi
    TAG1="(new CLink(SPACE, 'h";
    LINHA=`cat $ARQUIVO | sed -ne "/$TAG1/{=;q;}"`;
    sed -i "s/$TAG1/#$TAG1/g" $ARQUIVO
    TAG1="(new CLink('', ''))->setAttribute('onclick','zbxeSearch(\"doc\");')";
    sed -i "$LINHA i\\$TAG_INICIO\n$TAG1\n$TAG_FINAL" $ARQUIVO;

    #Comentando o target
    TAG1="->setAttribute('target', '_blank')";
    sed -i "s/$TAG1/#$TAG1/g" $ARQUIVO

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
#    sed -i "$FIMINST i if(strpos(strtolower(\$options['units']),'literal-') > -1){ \$sufixo=explode('-',\$options['units']); $options['units'] = \" \" . $sufixo[1]; }" $ARQUIVO
    sed -i "$FIMINST i if(strpos(strtolower(\$options['units']),'literal') > -1){ \$sufixo=explode('-',\$options['units']); return round(\$options['value']).\" \".\$sufixo[1]; }" $ARQUIVO
#    sed -i "$FIMINST i if(strpos(strtolower(\$options['units']),'literal') > -1){ \$sufixo=explode('-',\$options['units']); return round(\$options['value'], ZBX_UNITS_ROUNDOFF_UPPER_LIMIT).\" \".\$sufixo[1]; }" $ARQUIVO
    FIMINST=$(($FIMINST+1));
}

corTituloMapa() {
    # Arquivo com as principais definicoes dos mapas ===========================
    ARQUIVO="include/classes/sysmaps/CMapPainter.php";
    if [ -f "$ARQUIVO" ]; then
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
    fi
    # Arquivo com as principais definicoes dos mapas ===========================
    ARQUIVO="include/classes/sysmaps/CCanvas.php";
    if [ -f "$ARQUIVO" ]; then
        backupArquivo $ARQUIVO;
        sed -i "s/\$this->width - .*, \$this->height - 12, .*\$date/\$this->width - zbxeCompanyNameSize(), \$this->height - 12, zbxeCompanyName().\$date/" $ARQUIVO;
    fi
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
    if [ -f "$ARQ_TMP" ]; then
        if [ -e $DIR_TMP ]; then
            rm -rf $DIR_TMP;
        fi
        unzip $ARQ_TMP > /tmp/tmp_unzip_ze.log;
        cd $DIR_TMP
        if [ ! -e "$DIR_DEST" ]; then
            mkdir -p "$DIR_DEST";
        fi 
    else
        echo "===>Instalacao usando cache local";
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
    if [ "$DOWNLOADFILES" = "" ]; then
        dialog \
            --title 'Download /tmp/EveryZ.zip'        \
            --radiolist "$M_DOWNLOAD_FILE"  \
            0 0 0                                    \
            S   "$M_DOWNLOAD_SIM"  on    \
            N   "$M_DOWNLOAD_NAO"  off   \
            2> $TMP_DIR/resposta_dialog.txt
        DOWNLOADFILES=`cat $TMP_DIR/resposta_dialog.txt `;
    fi
}

apacheDirectoryConf() {
    echo "<Directory \"$CAMINHO_FRONTEND/local/app/everyz/$1\"> " >> $APACHEROOT/everyz.conf
    echo " Options FollowSymLinks " >> $APACHEROOT/everyz.conf
    echo " AllowOverride All " >> $APACHEROOT/everyz.conf
    echo " <IfModule mod_authz_core.c> " >> $APACHEROOT/everyz.conf
    echo "  Require all granted " >> $APACHEROOT/everyz.conf
    echo " </IfModule>" >> $APACHEROOT/everyz.conf
    echo " Order allow,deny" >> $APACHEROOT/everyz.conf
    echo " Allow from all" >> $APACHEROOT/everyz.conf
    echo "</Directory>" >> $APACHEROOT/everyz.conf;
}

configuraApache() {
    if [ "$RECONFAPACHE" = "S" ]; then
        # Localizar onde estão os arquivos de configuração do apache
        APACHEROOT=$(apachectl -V 2> /dev/null | grep HTTPD | awk -F= '{print $2}' | sed 's/"//g' );
        if [ -d "$APACHEROOT/conf.d" ]; then
            APACHEROOT=$APACHEROOT"/conf.d";
        else
            APACHEROOT=$APACHEROOT"/conf-enabled";
        fi
        if [ -d "$APACHEROOT" ]; then
            # Adicionar o arquivo de configuração do everyz
            BASECONF="# Allow to read images, scripts, css files on EveryZ installation ";
            echo "$BASEZCONF" > "$APACHEROOT/everyz.conf";
            apacheDirectoryConf "js";
            apacheDirectoryConf "images";
            apacheDirectoryConf "css";
            if [ -f "/etc/init.d/apache2" ]; then
                /etc/init.d/apache2 restart ;
            elif [ -f "/etc/init.d/httpd" ]; then
                /etc/init.d/httpd restart ;
            else
                service httpd restart
            fi
            registra "Reconfigurou o apache! $APACHEROOT/everyz.conf  ";
        fi
    else 
        registra "Nao reconfigurou o apache!";
    fi
}

confirmaApache() {
    if [ "$RECONFAPACHE" = "" ]; then
        dialog \
            --title 'Apache'        \
            --radiolist "$M_CONFAPACHE"  \
            0 0 0                                    \
            S   "$M_CONFAPACHE_SIM"  on    \
            N   "$M_CONFAPACHE_NAO"  off   \
            2> $TMP_DIR/resposta_dialog.txt
        RECONFAPACHE=`cat $TMP_DIR/resposta_dialog.txt `;
    fi
}

instalaPortletNS() {
    cd $CAMINHO_FRONTEND;
    registra "Configurando portlet com link para itens nao suportados...";
    ARQUIVO="include/blocks.inc.php";
    TAG_INICIO='##Zabbix-Extras-NS-custom';
    TAG_FINAL="$TAG_INICIO-FIM";
    INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
    FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
    if [ ! -z $INIINST ]; then
        installMgs "U" "NS"; 
    else
        installMgs "N" "NS"; 
        TMP="items_count_not_supported";
        INIINST=`cat $ARQUIVO | sed -ne "/$TMP/{=;q;}"`;
        FIMINST=$INIINST;
    fi
    sed -i "$INIINST,$FIMINST d" $ARQUIVO;
    TXT_CUSTOM="new CLink(\$status['items_count_not_supported']\, 'everyz.php?fullscreen=0&item=&action=zbxe-ns&format=0&inactiveHosts=1&filter_set=1')";
    sed -i "$INIINST i$TAG_INICIO\n$TXT_CUSTOM\n$TAG_FINAL" $ARQUIVO
}

function updatePopUp() {
    ARQUIVO="popup.php";
# '"itemid", "name", "master_itemname"',
    if [ -f "$ARQUIVO" ]; then
        TAG_INICIO='##Zabbix-Extras-POP-custom';
        TAG_FINAL="$TAG_INICIO-FIM";
        INIINST=`cat $ARQUIVO | sed -ne "/$TAG_INICIO/{=;q;}"`;
        FIMINST=`cat $ARQUIVO | sed -ne "/$TAG_FINAL/{=;q;}"`;
        if [ ! -z $INIINST ]; then
            installMgs "U" "POP"; 
        else
            installMgs "N" "POP"; 
            TMP='"name", "master_itemname"';
            INIINST=`cat $ARQUIVO | sed -ne "/$TMP/{=;q;}"`;
            FIMINST=$INIINST;
        fi
        sed -i "$INIINST,$FIMINST d" $ARQUIVO;
        TXT_CUSTOM="\t'items' => '\"itemid\", \"name\", \"master_itemname\", \"key_\" ', ";
        sed -i "$INIINST i$TAG_INICIO\n$TXT_CUSTOM\n$TAG_FINAL" $ARQUIVO
    fi
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
#tipoInstallZabbix;
caminhoFrontend;
confirmaDownload;
confirmaApache;
####### Download de arquivos ---------------------------------------------------

####### Instalacao -------------------------------------------------------------
instalaGit;  
primeiroAcesso;
instalaMenus;
customLogo;
instalaLiteral;
corTituloMapa;
configuraApache;
instalaPortletNS;
updatePopUp;

registra "Installed - [ $VERSAO_INST ]";
 
#echo "Installed - [ $VERSAO_INST ]";
#echo "You need to check your apache server and restart!";
