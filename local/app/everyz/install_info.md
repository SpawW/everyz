# EveryZ - Guia de instalação por parâmetros / Install Guide using params

A instalação do Everyz é feita através do script *installEveryz.sh*. Existindo um branch para cada macro versão do Zabbix que foi homologada.

[Eng: The EveryZ installation can be done using a script called *installEveryz.sh*. For each major Zabbix version exists a branch for him]

Este guia define quatro variáveis que só serão utilizadas durante o processo de instalação. Todos os passos aqui definidos devem ser **executados como root**.

[Eng: This guide defines four variables and these variables are used only for the installation process. All steps here defined **need to be run using a root privilege account**. ]

## Zabbix 3.4.x

Definir o valor da variável **BRANCH** com o valor **3.x**

[Eng: Define **BRANCH** with value **3.x**]

```
export BRANCH="3.x"
```


## Zabbix 4.0

Último build testado: **4.0.15**.

Definir o valor da variável **BRANCH** com o valor **4.0**

[Eng: Define **BRANCH** with value **4.0**]

```
export BRANCH="4.0"
```

## Zabbix 4.4

Último build testado: **4.4.3**.

Definir o valor da variável **BRANCH** com o valor **4.4**

[Eng: Define **BRANCH** with value **4.4**]

```
export BRANCH="4.4"
```

A segunda variável a ser configurada é a que define a distribuição que está sendo utilizada no frontend do Zabbix (Zabbix-Web).

[Eng: The second variable defines what is the distribution in use for Zabbix-web.]

## CENTOS / RedHat / Oracle Linux / Amazon

```
export DISTRO="centos";
```

## DEBIAN / UBUNTU 

```
export DISTRO="debian";
```

## OpenSuse

```
export DISTRO="opensuse";
```

## FreeBSD

```
export DISTRO="freebsd";
```

O terceiro parâmetro varia em função de distribuição e versão de distribuição, além de variar em função do servidor de aplicação também (apache ou nginx).

## Apache (Centos, Red Hat e outros)

```
export WEBSERVER="httpd";
```

## Apache (Debian)

```
export WEBSERVER="apache2";
```

## Apache (Ubuntu)

```
export WEBSERVER="apache";
```

## NGINX (Acredito que em todas as distros)

-- Importante: a configuração do servidor web terá que ser feita manualmente

```
export WEBSERVER="NGINX";
```

## Localização dos arquivos do Zabbix-Web / Zabbix-web location

Este caminho muda em cada distribuição, o **exemplo** abaixo é para o Centos 7 sem customizações.

```
export ZABBIXWEB_PATH="/usr/share/zabbix"
```

### Download

wget https://raw.githubusercontent.com/SpawW/everyz/$BRANCH/local/app/everyz/installEveryz.sh -O /tmp/installEveryz.sh

### Instalação / Install

bash /tmp/installEveryz.sh -a=S -f="$ZABBIXWEB_PATH" -d=S -l=pt -i=$DISTRO -b="$BRANCH" && service $WEBSERVER restart 
