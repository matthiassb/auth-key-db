<?php
function createKeysTableSql(){
  return <<< EOF
    CREATE TABLE IF NOT EXISTS `authorized_keys` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(255) NULL DEFAULT NULL,
    `pub_key` VARCHAR(500) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key_username` (`username`,`pub_key`)
  );
EOF;
}

function getKeysSql($user){
  return "SELECT * FROM `authorized_keys` WHERE username='{$user}'; ";
}

?>
