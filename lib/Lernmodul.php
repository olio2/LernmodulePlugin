<?php

class Lernmodul extends SimpleORMap {

    static protected function configure($config = array())
    {
        $config['db_table'] = 'lernmodule_module';
        parent::configure($config);
    }

    public function copyModule($path)
    {
        if (file_exists($this->getPath())) {
            $success = rmdirr($this->getPath());
        }
        $success = mkdir($this->getPath());
        if (!$success) {
            PageLayout::postMessage(MessageBox::error(_("Konnte im Dateisystem keinen Ordner f�r das Lernmodul anlegen.")));
        }
        $success = extract_zip($path, $this->getPath());
        if ($success) {
            foreach (scandir($this->getPath()) as $folder) {
                if (!in_array($folder, array(".", ".."))) {
                    break;
                }
            }
            rename($this->getPath()."/".$folder, $this->getPath()."/".$this->getId());
            $this->copyr(
                $this->getPath() . "/" . $this->getId(),
                $this->getPath()
            );
            rmdirr($this->getPath() . "/" . $this->getId());
            if (!$this['start_file'] || !file_exists($this->getPath()."/".$this['start_file'])) {
                if (file_exists($this->getPath()."/index.html")) {
                    $this['start_file'] = "index.html";
                } else {
                    $files = $this->scanForFiletypes(array("html", "htm"));
                    $this['start_file'] = $files[0];
                }
            }
            if (!$this['image'] || !file_exists($this->getPath()."/".$this['image'])) {
                $images = $this->scanForImages();
                $this['image'] = $images[0];
            }
            $this['type'] = file_exists($this->getPath() . "/h5p.json") ? "h5p" : "html";
            $this->store();
        } else {
            PageLayout::postMessage(MessageBox::error(_("Entzippen des Lernmoduls hat nicht geklappt.")));
        }
        return $success;
    }

    public function scanForImages()
    {
        return $this->scanForFiletypes(array("png", "jpg", "jpeg"));
    }

    public function scanForFiletypes($filetypes = array(), $path = null)
    {
        if (!$path) {
            $path = $this->getPath();
            $reduce = strlen($path) + 1;
        }
        $files = array();
        foreach (scandir($path) as $file) {
            if (!in_array($file, array(".", ".."))) {
                if (!is_dir($path."/".$file)) {
                    $file_part = array_pop(explode(".", $file));
                    if (in_array(strtolower($file_part), $filetypes)) {
                        $files[] = $path."/".$file;
                    }
                }
            }
        }
        sort($files);
        foreach (scandir($path) as $file) {
            if (!in_array($file, array(".", ".."))) {
                if (is_dir($path."/".$file)) {
                    foreach ($this->scanForFiletypes($filetypes, $path."/".$file) as $image) {
                        $files[] = $image;
                    }
                }
            }
        }
        if ($reduce) {
            foreach ($files as $key => $file) {
                $files[$key] = substr($file, $reduce);
            }
        }
        if ($GLOBALS['FILESYSTEM_UTF8']) {
            $files = studip_utf8decode($files);
        }
        return $files;
    }

    protected function copyr($source, $dest) {
        if(is_dir($source)) {
            $dir_handle=opendir($source);
            while($file=readdir($dir_handle)){
                if($file!="." && $file!=".."){
                    if(is_dir($source."/".$file)){
                        if(!is_dir($dest."/".$file)){
                            mkdir($dest."/".$file);
                        }
                        $this->copyr($source."/".$file, $dest."/".$file);
                    } else {
                        copy($source."/".$file, $dest."/".$file);
                    }
                }
            }
            closedir($dir_handle);
        } else {
            copy($source, $dest);
        }
    }

    public function getPath()
    {
        return __DIR__."/../moduledata/".$this->getId();
    }

    public function getURL()
    {
        return $GLOBALS['ABSOLUTE_URI_STUDIP']."plugins_packages/RasmusFuhse/LernmodulePlugin/moduledata/".$this->getId();
    }

    public function getStartURL()
    {
        return $this->getURL()."/".($this['start_file'] ?: "index.html");
    }

}