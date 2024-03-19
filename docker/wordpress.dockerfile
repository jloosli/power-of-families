FROM wordpress:php7.4
# TT version as of 2/2024: https://support.tigertech.net/wordpress-version

# Next lines needed for Zscaler
ADD ./docker/ZscalerRootCA.crt /tmp/ZscalerRootCA.crt
RUN <<EOF
export CERT_DIR=$(openssl version -d | cut -f2 -d '"')/certs
cp /tmp/ZscalerRootCA.crt $CERT_DIR
update-ca-certificates --fresh
EOF