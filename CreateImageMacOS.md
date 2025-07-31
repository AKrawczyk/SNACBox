MacOS Command line<br>
Insert SD Card<br>

```bash
diskutil list
```

Find SD Card<br>


```bash
diskutil unmountDisk /dev/diskX
sudo dd if=/dev/rdisk4 of=Downloads/SNACBox-OpenWRT-x.x.xx.xx.img bs=4m status=progress
```

<br>Linux Command line
<br>Copy over SNACBox-OpenWRT-x.x.xx.xx.img file

```bash
wget https://raw.githubusercontent.com/Drewsif/PiShrink/master/pishrink.sh
chmod +x pishrink.sh
sudo mv pishrink.sh /usr/local/bin
sudo pishrink.sh SNACBox-OpenWRT-x.x.xx.xx.img
```
This will shrink the img file to fit diffrent SD cards.<br>
Now the image can be imaged onto SD card by Pi Imager
