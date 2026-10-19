#!/bin/sh

#-------- AdGuardhome Configuration Activation --------
if [ -f /etc/config/adguardhome.txt ]; then
    echo "AdGuardhome configuration file found, configuring plugin..."
    mv /etc/config/adguardhome /etc/config/adguardhome.org
    mv /etc/config/adguardhome.txt /etc/config/adguardhome
fi
if [ ! -f /etc/adguardhome/adguardhome.yaml ]; then
    echo "Enable AdGuardhome configuration file..."
    mv /etc/adguardhome/adguardhome.txt /etc/adguardhome/adguardhome.yaml
else
    echo "AdGuardhome configuration file already exists, backup and replace..."
    mv /etc/adguardhome/adguardhome.yaml /etc/adguardhome/adguardhome.org
    mv /etc/adguardhome/adguardhome.txt /etc/adguardhome/adguardhome.yaml
fi
/etc/init.d/adguardhome restart