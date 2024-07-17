# General

....

# Known issues

## LZMA native GCC version fix

ImportError: /lib64/libstdc++.so.6: version `CXXABI_1.3.8' not found

### Solution

https://superuser.com/questions/1432321/importerror-lib64-libstdc-so-6-version-cxxabi-1-3-8-not-found

# CENTOS 7 dependencies 
## Install dependencies
`sudo yum check-update`
`sudo yum -y install wget make gcc-c++`

## Download gcc new version
`wget -O - 'https://ftpmirror.gnu.org/gcc/gcc-7.3.0/gcc-7.3.0.tar.xz' | tar -xJ`

## This helps if you are behind a proxy
`sed -i 's/ftp:/https:/' ./gcc-7.3.0/contrib/download_prerequisites`

## Finally compile gcc
`( cd gcc-7.3.0 && ./contrib/download_prerequisites && mkdir build && cd build && ../configure --enable-checking=release --enable-languages=c,c++ --disable-multilib && make -j 8 && sudo make install ) && rm -fR gcc-7.3.0`

## Unlink current version
`sudo unlink /usr/lib64/libstdc++.so.6`

## Copy new version
`sudo cp /usr/local/lib64/libstdc++.so.6 /usr/lib64`
