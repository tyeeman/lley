<?php
// Blog plugin, https://github.com/datenstrom/yellow-plugins/tree/master/blog
// Copyright (c) 2013-2018 Datenstrom, https://datenstrom.se
// This file may be used and distributed under the terms of the public license.

class YellowBlog {
    const VERSION = "0.7.7";
    public $yellow;         //access to API
    
    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->config->setDefault("blogLocation", "");
        $this->yellow->config->setDefault("blogNewLocation", "@title");
        $this->yellow->config->setDefault("blogPagesMax", "10");
        $this->yellow->config->setDefault("blogPaginationLimit", "5");
    }
    
    // Handle page content of custom block
    public function onParseContentBlock($page, $name, $text, $shortcut) {
        $output = null;
        if ($shortcut) {
            switch($name) {
                case "blogarchive": $output = $this->getShorcutBlogarchive($page, $name, $text); break;
                case "blogauthors": $output = $this->getShorcutBlogauthors($page, $name, $text); break;
                case "blogpages":   $output = $this->getShorcutBlogpages($page, $name, $text); break;
                case "blogchanges": $output = $this->getShorcutBlogchanges($page, $name, $text); break;
                case "blogrelated": $output = $this->getShorcutBlogrelated($page, $name, $text); break;
                case "blogtags":    $output = $this->getShorcutBlogtags($page, $name, $text); break;
            }
        }
        return $output;
    }
        
    // Return blogarchive shortcut
    public function getShorcutBlogarchive($page, $name, $text) {
        $output = null;
        list($location, $pagesMax) = $this->yellow->toolbox->getTextArgs($text);
        if (empty($location)) $location = $this->yellow->config->get("blogLocation");
        if (strempty($pagesMax)) $pagesMax = $this->yellow->config->get("blogPagesMax");
        $blog = $this->yellow->pages->find($location);
        $pages = $this->getBlogPages($location);
        $page->setLastModified($pages->getModified());
        $months = $this->getMonths($pages, "published");
        if (count($months)) {
            if ($pagesMax!=0) $months = array_slice($months, -$pagesMax);
            uksort($months, "strnatcasecmp");
            $months = array_reverse($months);
            $output = "<div class=\"".htmlspecialchars($name)."\">\n";
            $output .= "<ul>\n";
            foreach ($months as $key=>$value) {
                $output .= "<li><a href=\"".$blog->getLocation(true).$this->yellow->toolbox->normaliseArgs("published:$key")."\">";
                $output .= htmlspecialchars($this->yellow->text->normaliseDate($key))."</a></li>\n";
            }
            $output .= "</ul>\n";
            $output .= "</div>\n";
        } else {
            $page->error(500, "Blogarchive '$location' does not exist!");
        }
        return $output;
    }
    
    // Return blogauthors shortcut
    public function getShorcutBlogauthors($page, $name, $text) {
        $output = null;
        list($location, $pagesMax) = $this->yellow->toolbox->getTextArgs($text);
        if (empty($location)) $location = $this->yellow->config->get("blogLocation");
        if (strempty($pagesMax)) $pagesMax = $this->yellow->config->get("blogPagesMax");
        $blog = $this->yellow->pages->find($location);
        $pages = $this->getBlogPages($location);
        $page->setLastModified($pages->getModified());
        $authors = $this->getMeta($pages, "author");
        if (count($authors)) {
            $authors = $this->yellow->lookup->normaliseUpperLower($authors);
            if ($pagesMax!=0 && count($authors)>$pagesMax) {
                uasort($authors, "strnatcasecmp");
                $authors = array_slice($authors, -$pagesMax);
            }
            uksort($authors, "strnatcasecmp");
            $output = "<div class=\"".htmlspecialchars($name)."\">\n";
            $output .= "<ul>\n";
            foreach ($authors as $key=>$value) {
                $output .= "<li><a href=\"".$blog->getLocation(true).$this->yellow->toolbox->normaliseArgs("author:$key")."\">";
                $output .= htmlspecialchars($key)."</a></li>\n";
            }
            $output .= "</ul>\n";
            $output .= "</div>\n";
        } else {
            $page->error(500, "Blogauthors '$location' does not exist!");
        }
        return $output;
    }

    // Return blogpages shortcut
    public function getShorcutBlogpages($page, $name, $text) {
        $output = null;
        list($location, $pagesMax, $author, $tag) = $this->yellow->toolbox->getTextArgs($text);
        if (empty($location)) $location = $this->yellow->config->get("blogLocation");
        if (strempty($pagesMax)) $pagesMax = $this->yellow->config->get("blogPagesMax");
        $blog = $this->yellow->pages->find($location);
        $pages = $this->getBlogPages($location);
        if (!empty($author)) $pages->filter("author", $author);
        if (!empty($tag)) $pages->filter("tag", $tag);
        $pages->sort("title");
        $page->setLastModified($pages->getModified());
        if (count($pages)) {
            if ($pagesMax!=0) $pages->limit($pagesMax);
            $output = "<div class=\"".htmlspecialchars($name)."\">\n";
            $output .= "<ul>\n";
            foreach ($pages as $page) {
                $output .= "<li><a".($page->isExisting("tag") ? " class=\"".$this->getClass($page)."\"" : "");
                $output .=" href=\"".$page->getLocation(true)."\">".$page->getHtml("title")."</a></li>\n";
            }
            $output .= "</ul>\n";
            $output .= "</div>\n";
        } else {
            $page->error(500, "Blogpages '$location' does not exist!");
        }
        return $output;
    }
    
    // Return blogchanges shortcut
    public function getShorcutBlogchanges($page, $name, $text) {
        $output = null;
        list($location, $pagesMax, $author, $tag) = $this->yellow->toolbox->getTextArgs($text);
        if (empty($location)) $location = $this->yellow->config->get("blogLocation");
        if (strempty($pagesMax)) $pagesMax = $this->yellow->config->get("blogPagesMax");
        $blog = $this->yellow->pages->find($location);
        $pages = $this->getBlogPages($location);
        if (!empty($author)) $pages->filter("author", $author);
        if (!empty($tag)) $pages->filter("tag", $tag);
        $pages->sort("published", false);
        $page->setLastModified($pages->getModified());
        if (count($pages)) {
            if ($pagesMax!=0) $pages->limit($pagesMax);
            $output = "<div class=\"".htmlspecialchars($name)."\">\n";
            $output .= "<ul>\n";
            foreach ($pages as $page) {
                $output .= "<li><a".($page->isExisting("tag") ? " class=\"".$this->getClass($page)."\"" : "");
                $output .=" href=\"".$page->getLocation(true)."\">".$page->getHtml("title")."</a></li>\n";
            }
            $output .= "</ul>\n";
            $output .= "</div>\n";
        } else {
            $page->error(500, "Blogchanges '$location' does not exist!");
        }
        return $output;
    }
        
    // Return blogrelated shortcut
    public function getShorcutBlogrelated($page, $name, $text) {
        $output = null;
        list($location, $pagesMax) = $this->yellow->toolbox->getTextArgs($text);
        if (empty($location)) $location = $this->yellow->config->get("blogLocation");
        if (strempty($pagesMax)) $pagesMax = $this->yellow->config->get("blogPagesMax");
        $blog = $this->yellow->pages->find($location);
        $pages = $this->getBlogPages($location);
        $pages->similar($page->getPage("main"));
        $page->setLastModified($pages->getModified());
        if (count($pages)) {
            if ($pagesMax!=0) $pages->limit($pagesMax);
            $output = "<div class=\"".htmlspecialchars($name)."\">\n";
            $output .= "<ul>\n";
            foreach ($pages as $page) {
                $output .= "<li><a".($page->isExisting("tag") ? " class=\"".$this->getClass($page)."\"" : "");
                $output .= " href=\"".$page->getLocation(true)."\">".$page->getHtml("title")."</a></li>\n";
            }
            $output .= "</ul>\n";
            $output .= "</div>\n";
        } else {
            $page->error(500, "Blogrelated '$location' does not exist!");
        }
        return $output;
    }
    
    // Return blogtags shortcut
    public function getShorcutBlogtags($page, $name, $text) {
        $output = null;
        list($location, $pagesMax) = $this->yellow->toolbox->getTextArgs($text);
        if (empty($location)) $location = $this->yellow->config->get("blogLocation");
        if (strempty($pagesMax)) $pagesMax = $this->yellow->config->get("blogPagesMax");
        $blog = $this->yellow->pages->find($location);
        $pages = $this->getBlogPages($location);
        $page->setLastModified($pages->getModified());
        $tags = $this->getMeta($pages, "tag");
        if (count($tags)) {
            $tags = $this->yellow->lookup->normaliseUpperLower($tags);
            if ($pagesMax!=0 && count($tags)>$pagesMax) {
                uasort($tags, "strnatcasecmp");
                $tags = array_slice($tags, -$pagesMax);
            }
            uksort($tags, "strnatcasecmp");
            $output = "<div class=\"".htmlspecialchars($name)."\">\n";
            $output .= "<ul>\n";
            foreach ($tags as $key=>$value) {
                $output .= "<li><a href=\"".$blog->getLocation(true).$this->yellow->toolbox->normaliseArgs("tag:$key")."\">";
                $output .= htmlspecialchars($key)."</a></li>\n";
            }
            $output .= "</ul>\n";
            $output .= "</div>\n";
        } else {
            $page->error(500, "Blogtags '$location' does not exist!");
        }
        return $output;
    }
    
    // Handle page template
    public function onParsePageTemplate($page, $name) {
        if ($name=="blogpages") {
            $pages = $this->getBlogPages($this->yellow->page->location);
            $pagesFilter = array();
            if ($_REQUEST["tag"]) {
                $pages->filter("tag", $_REQUEST["tag"]);
                array_push($pagesFilter, $pages->getFilter());
            }
            if ($_REQUEST["author"]) {
                $pages->filter("author", $_REQUEST["author"]);
                array_push($pagesFilter, $pages->getFilter());
            }
            if ($_REQUEST["published"]) {
                $pages->filter("published", $_REQUEST["published"], false);
                array_push($pagesFilter, $this->yellow->text->normaliseDate($pages->getFilter()));
            }
            $pages->sort("published");
            $pages->pagination($this->yellow->config->get("blogPaginationLimit"));
            if (!$pages->getPaginationNumber()) $this->yellow->page->error(404);
            if (!empty($pagesFilter)) {
                $title = implode(" ", $pagesFilter);
                $this->yellow->page->set("titleHeader", $title." - ".$this->yellow->page->get("sitename"));
                $this->yellow->page->set("titleBlog", $this->yellow->page->get("title").": ".$title);
            }
            $this->yellow->page->setPages($pages);
            $this->yellow->page->setLastModified($pages->getModified());
            $this->yellow->page->setHeader("Cache-Control", "max-age=60");
        }
        if ($name=="blog") {
            $location = $this->yellow->config->get("blogLocation");
            if (empty($location)) $location = $this->yellow->lookup->getDirectoryLocation($this->yellow->page->location);
            $blog = $this->yellow->pages->find($location);
            $this->yellow->page->setPage("blog", $blog);
        }
    }
    
    // Handle content file editing
    public function onEditContentFile($page, $action) {
        if ($page->get("template")=="blog") $page->set("pageNewLocation", $this->yellow->config->get("blogNewLocation"));
    }

    // Return blog pages
    public function getBlogPages($location) {
        $pages = $this->yellow->pages->clean();
        $blog = $this->yellow->pages->find($location);
        if ($blog) {
            if ($location==$this->yellow->config->get("blogLocation")) {
                $pages = $this->yellow->pages->index(!$blog->isVisible());
            } else {
                $pages = $blog->getChildren(!$blog->isVisible());
            }
            $pages->filter("template", "blog");
        }
        return $pages;
    }
    
    // Return class for page
    public function getClass($page) {
        if ($page->isExisting("tag")) {
            foreach (preg_split("/\s*,\s*/", $page->get("tag")) as $tag) {
                $class .= " tag-".$this->yellow->toolbox->normaliseArgs($tag, false);
            }
        }
        return trim($class);
    }
    
    // Return meta data from page collection
    public function getMeta($pages, $key) {
        $data = array();
        foreach ($pages as $page) {
            if ($page->isExisting($key)) {
                foreach (preg_split("/\s*,\s*/", $page->get($key)) as $entry) {
                    ++$data[$entry];
                }
            }
        }
        return $data;
    }
    
    // Return months from page collection
    public function getMonths($pages, $key) {
        $data = array();
        foreach ($pages as $page) {
            if (preg_match("/^(\d+\-\d+)\-/", $page->get($key), $matches)) ++$data[$matches[1]];
        }
        return $data;
    }
}

$yellow->plugins->register("blog", "YellowBlog", YellowBlog::VERSION);
