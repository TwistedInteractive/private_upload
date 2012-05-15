<?php

Class extension_private_upload extends Extension
{

    /**
     * De-Installation
     * @return void
     */
    public function uninstall()
    {
        Symphony::Database()->query('DROP TABLE `tbl_fields_private_upload`;');
    }

    /**
     * Installation
     * @return void
     */
    public function install()
    {
        Symphony::Database()->query('
            CREATE TABLE IF NOT EXISTS `tbl_fields_private_upload` (
              `id` int(11) unsigned NOT NULL auto_increment,
              `field_id` int(11) unsigned NOT NULL,
              `destination` varchar(255) collate utf8_unicode_ci default NULL,
              `validator` varchar(255) collate utf8_unicode_ci default NULL,
              PRIMARY KEY  (`id`),
              KEY `field_id` (`field_id`)
            );
        ');
    }
}