#!/bin/sh
set -xe

: ${CLOSURE_VERSION:=20161024}

wget -c https://dl.google.com/closure-compiler/compiler-$CLOSURE_VERSION.zip -O vendor/compiler-$CLOSURE_VERSION.zip
unzip -od vendor/closure-compiler vendor/compiler-$CLOSURE_VERSION.zip
ln -sfn ../vendor/closure-compiler/closure-compiler-v$CLOSURE_VERSION.jar tests/compiler.jar

# test that version matches
out=$(java -jar tests/compiler.jar --version)

version=$(echo "$out" | awk '/Version:/{print $NF}')
version=${version#v}

test "$version" = "$CLOSURE_VERSION"
