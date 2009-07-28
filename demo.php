<?php
require_once 'chelvi.php';

class demoProject{
    var $key = 'o';
    
    private function header($title=''){
        $op = '<html><head><title>'.$title.'</title>'.
              '</head><body>';
        return $op;
    }

    private function footer(){
        return '</body></html>';
    }

    private function pageRender($title='', $content=''){
        $op = $this->header($title) . $content . $this->footer();
        return $op;
    }


    function home(){
        $msg = '<h1>Hi, Welcome to my Webpage</h1>';
        return $this->pageRender('Home page Title',$msg);  // We can use echo also
    }

    // Example: http://localhost/demo.php?o=blog
    function blog($options='view', $id='') {
        $msg = '<h2>Hi, This is my blog page</h2>';
        return $this->pageRender('Blog page Title',$msg);  // We can use echo also
    }

    function error($errId='404'){
        return $this->pageRender('Error!', 'Error Occured');
    }
}

// Start Project
chelvi::start(new demoProject());

?>
