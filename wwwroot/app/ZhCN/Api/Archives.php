<?php

namespace App\ZhCN\Api;

if (!defined('IN_PX'))
    exit;

use App\Repository;

class Archives {

    private function __RestController() {}

    private function __Route($value = '/api/archives') {}

    protected function __Inject(Repository\Archives $repoArc) {}

    public function view() {
        $this->repoArc->viewCount($_POST['id']);
        return true;
    }

}
