<?php
// Blogsite theme, https://github.com/datenstrom/yellow-themes/tree/master/blogsite
// Copyright (c) 2013-2018 Datenstrom, https://datenstrom.se
// This file may be used and distributed under the terms of the public license.

class YellowThemeBlogsite {
    const VERSION = "0.7.6";
}

$yellow->themes->register("blogsite", "YellowThemeBlogsite", YellowThemeBlogsite::VERSION);
