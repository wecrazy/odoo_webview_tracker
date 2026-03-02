<?php

class html {
    public function __construct() {
        echo <<<EOD
            <!DOCTYPE html>

            <html
            lang="en"
            class="light-style layout-navbar-fixed layout-compact customizer-hide"
            dir="ltr"
            data-theme="theme-default"
            data-assets-path="public/assets/"
            data-template="vertical-menu-template"
            >

        EOD;
    }

    public function htmlClose() {
        echo "</html>";
    }
}