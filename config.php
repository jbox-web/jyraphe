;; -*- mode: Conf -*-
;<?php header("location: i_do_not_exist.html"); exit;?>
; .ini file.
; config.php
;
; This was automatically generated and contains the configuration for
; your jyraphe installation. Edit with care.

[Core]
; Must be located outside of docroot
var_root = /data/jyraphe/var-sc6Qu8Lpx4dV1ss/

hash_size = 32
from_email = jyraphe@example.fr
smtp_host = 127.0.0.1
smtp_auth = false
smtp_port = 25
smtp_username = ""
smtp_password = ""
disable_infinity = false
rewrite = true
password = ""

[Cleaner]
enabled = true

; Comma-separated IP addresses list. (don't put space after comma)
allow_ips = 127.0.0.1

[Interface]
web_root = http://jyraphe.example.fr/
style = default
jyraphe_package = Jyraphe
lang = fr_FR.UTF-8

;Default validity period. Available options: 1m, 1h, 1d, 1w, 1M, F (forever)
validity = 1w
