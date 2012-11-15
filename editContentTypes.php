<?php
$title = 'Edit all Content Types across sites';

$type_override = 'contenttypecontainer';
$start_asset = '019ab7fa7f00000101f92de526136095,019b3c707f000002221c3dfe8cd145aa,047362a37f00000101f92de5d3d8e0de,049934cc7f0000020102c0657aac68f0,0527061b7f00000215fab3c5904fca52,0760b6277f00000101fa0f191c9ca15f,0ffd1c857f00000101f92de51b6ffe63,1304ec207f0000022b80da550b7c427c,1697b1127f00000101f92de5f178f320,2829e2937f0000021312656bbe69c3ed,2891e65c7f00000101b7715df072adf9,289c1b837f00000101b7715d3ec8b60b,2bd742667f0000021312656bea9192b1,2f7dcc757f00000101f92de5a037b270,3bb2785c7f00000209340e791e0ae29a,3beb3eb77f00000209340e79d222958b,4e9e14e77f000001015d84e099e6d852,51d8cc0e7f00000101f92de5aa7e3e08,5272df2c7f00000101f92de515892069,548d21a57f00000101f92de530b054bc,548fa7347f00000101f92de52f6cd605,63eb0a667f00000101f92de57df78bfb,640f30d97f00000101f92de57b78c67f,6b8db79f7f000002007f6ff8e7112473,6b8f3cc47f000002007f6ff8b74813cb,73fa79797f00000250ffdf132f64b0eb,75e2256b7f00000101f92de5e8d16857,778ad2ae7f00000101f92de55b904286,77bd3db07f00000101f92de5a660c88c,817374027f00000101f92de516b7a69b,8cba5a1d7f0000025a3b87306456c77c,8e0a7d2f7f000002788556133e67d087,931fa3347f0000020f1c572a06279003,94a216ef7f000001015d84e0bf07289d,981015857f00000100279c88c6cca22a,9a5e1d457f00000101f92de5e4f11e1b,a55141d37f00000274a0ceefbd9877cf,a5784eaa7f00000101f92de5f02545ba,ab88123e7f0000021a23b0062d575964,af3e1cb07f000001016a5ae9b051e9e8,b1e7bfe27f00000100279c8818f75a72,b70b15597f00000100279c8863b4d342,c3d215047f00000101f92de58c8baaab,c621c14f7f00000101f92de58c1fbdee,c622917c7f00000101f92de532165f20,caf836ab7f00000244547c9da7a01962,cc912d917f0000020102c065e157f014,cd67aeab7f00000101f92de5c248414d,cd70cd247f00000101f92de5d13e3014,d89893977f0000022e208d448312bcce,def5051b7f00000204ada1dc75c15995,e02d9fa47f000002095adf3c527acd2c,e59ccc117f00000100c46dcf21459409,280690227f0000022d407b91a854971c';

// Optionally override the container/child types
$asset_type = 'contentTypeContainer';
$asset_children_type = 'contentType';

function pagetest($child) {
  // if (preg_match('/[a-z]/', $child->path->path))
    return true;
}
function foldertest($child) {
  // if (preg_match('/^[a-z]/', $child->path->path))
    return true;
}
function edittest($asset) {
  // if (preg_match('/[a-z]/', $asset["contentTypePath"]))
    return true;
}

function changes(&$asset) {
  /* If you wish to use $changed, make sure it's global, and set it to false. 
   * When something is changed, it becomes true: */
  // global $changed;
  $changed = false;
  // if ($asset["metadata"]->teaser != 'test') {$changed = true;}
  // $asset["metadata"]->teaser = 'test';
}


if (!$cron)
  include('header.php');

?>