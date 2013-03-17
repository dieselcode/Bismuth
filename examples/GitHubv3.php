<?php

include '../vendor/autoload.php';

try {
    $github = new \Bismuth\Endpoint\GitHub(
        new \Bismuth\Core\Auth\Basic('<username>', '<password>')
    );

    // update our repo with the patch data
    $api = $github->user()->current()->emails()->get();

    // true = it worked, false = we failed
    var_dump($api);

} catch (Exception $e) {
    var_dump($e->getMessage());
}
?>