<?php
date_default_timezone_set('America/New_York');
$title = 'Cron job to create an Info DD block, assign it, and grant access to faculty pages that do not have one';

// $type_override = 'page';
$start_asset = '2891e3f87f00000101b7715d1ba2a7fb';

function pagetest($child) {
  if (isset($_GET['name'])) {
    if (preg_match("/^".$_GET['name']."[a-z]/",$child->path->path)) {
      return true;
    }
  } else {
    if ($child->path->path != 'index' && preg_match('/^[a-z]/',$child->path->path)) {
      return true;
    }
  }
}
function foldertest($child) {
  return false;
}
function edittest($asset) {
  if (preg_match('/slc-faculty\/Faculty/', $asset["contentTypePath"]))
    return true;
}

function arrayContainsInfoBlock(array $myArray, $word) {
  foreach ($myArray as $element) {
    if ($element->identifier == $word) {
      return true;
    }
  }
  return false;
}

function changes(&$asset, $blockID) {
  /* If you wish to use $changed, make sure it's global, and set it to false. 
   * When something is changed, it becomes true: */
  global $changed;
  $changed = false;
  if ( arrayContainsInfoBlock($asset["structuredData"]->structuredDataNodes->structuredDataNode, "info-block") ) {
    foreach ($asset["structuredData"]->structuredDataNodes->structuredDataNode as $sdnode) {
      if ($sdnode->identifier == "info-block") {
        $sdnode->blockPath = 'faculty-blocks/'.$asset['name'];
        $sdnode->blockId = $blockID;
        $changed = true;
      }
    }
  } else {
    
    $info = new stdClass();
    $info->type = 'asset';
    $info->identifier = 'info-block';
    $info->assetType = 'block';
    $info->blockPath = 'faculty-blocks/'.$asset['name'];
    $info->blockId = $blockID;
    
    array_push($asset["structuredData"]->structuredDataNodes->structuredDataNode, $info);
    $changed = true;
    
  }
  
}

if (!$cron) {include('../html_header.php');}



function readFolder($client, $auth, $id) {
  global $asset_type, $asset_children_type, $data, $o, $cron;
  $folder = $client->read ( array ('authentication' => $auth, 'identifier' => $id ) );
  if ($folder->readReturn->success == 'true') {
    
    $asset = ( array ) $folder->readReturn->asset->$asset_type;
    if ($cron) {
      $o[4] .= "<h4>Folder: ".$asset["path"]."</h4>";
    } elseif ($_POST['folder'] == 'on') {
      echo "<h1>Folder: ".$asset["path"]."</h1>";
    }
    if ($_POST['children'] == 'on' && !$cron) {
      echo '<button class="btn" href="#cModal'.$asset['id'].'" data-toggle="modal">View Children</button><div id="cModal'.$asset['id'].'" class="modal hide" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-body">';
        print_r($asset["children"]); // Shows all the children of the folder
      echo '</div></div>';
    }
    indexFolder($client, $auth, $asset);
  } else {
    if ($cron) {
      $o[1] .= '<div style="padding:3px;color:#fff;background:#c00;">Failed to read folder: '.$asset["path"].'</div>';
    } else {
      echo '<div class="f">Failed to read folder: '.$asset["path"].'</div>';
    }
  }
}
function indexFolder($client, $auth, $asset) {
  global $asset_type, $asset_children_type, $data, $o, $cron;
  if (!is_array($asset["children"]->child)) {
    $asset["children"]->child=array($asset["children"]->child);
  }
  foreach($asset["children"]->child as $child) {
    if ($child->type == strtolower($asset_children_type)) {
      if (pagetest($child))
        readPage($client, $auth, array ('type' => $child->type, 'id' => $child->id), $child->type);
    } elseif ($child->type === strtolower($asset_type)) {
      if (foldertest($child))
        readFolder($client, $auth, array ('type' => $child->type, 'id' => $child->id));
    }
  }
}

function readPage($client, $auth, $id, $type) {
  global $asset_type, $asset_children_type, $data, $o, $cron;
  $reply = $client->read ( array ('authentication' => $auth, 'identifier' => $id ) );
  if ($reply->readReturn->success == 'true') {
    // For some reason the names of asset differ from the returned object
    $returned_type = '';
    foreach ($reply->readReturn->asset as $t => $a) {
      if (!empty($a)) {$returned_type = $t;}
    }
    
    $asset = ( array ) $reply->readReturn->asset->$returned_type;
    if ($cron) {
      $o[3] .= '<h4><a href="https://cms.slc.edu:8443/entity/open.act?id='.$asset['id'].'&type='.$type.'#highlight">'.$asset['path']."</a></h4>";
    } elseif ($_POST['asset'] == 'on') {
      $name = '';
      if (!$asset['path']) {$name = $asset['name'];}
      echo '<h4><a href="https://cms.slc.edu:8443/entity/open.act?id='.$asset['id'].'&type='.$type.'#highlight">'.$asset['path'].$name."</a></h4>";
    }
    
    if (edittest($asset)) {
      if (!$cron) {echo '<div class="page">';}
      if ($_POST['before'] == 'on' && !$cron) {
        echo '<button class="btn" href="#bModal'.$asset['id'].'" data-toggle="modal">View Before</button><div id="bModal'.$asset['id'].'" class="modal hide" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-body">';
          print_r($asset); // Shows the page in all its glory
        echo '</div></div>';
      }

      if (!$cron) {
        echo "<script type='text/javascript'>var page_".$asset['id']." = ";
        print_r(json_encode($asset));
        echo '; console.log(page_'.$asset['id'].')';
        echo "</script>";
      }
      
      editPage($client, $auth, $asset);
      if (!$cron) {echo '</div>';}
    }
    
  } else {
    if ($cron) {
      $o[1] .= '<div style="padding:3px;color:#fff;background:#c00;">Failed to read page: '.$id.'</div>';
    } else {
      echo '<div class="f">Failed to read page: '.$id.'</div>';
    }
  }
}


function editPage($client, $auth, $asset) {
  global $total, $asset_type, $asset_children_type, $data, $changed, $o, $cron;
  
  if ( arrayContainsInfoBlock($asset["structuredData"]->structuredDataNodes->structuredDataNode, "info-block") ) {
    foreach ($asset["structuredData"]->structuredDataNodes->structuredDataNode as $sdnode) {
      if ($sdnode->identifier == "info-block") {
        if (!preg_match('/[0-9]/', $sdnode->blockId) ) {
          echo 'hi';
          createAssignAccess($client, $auth, $asset);
        }
      }
    }
  } else {
    echo 'sdf';
    createAssignAccess($client, $auth, $asset);
  }
}




function createAssignAccess($client, $auth, $asset) {
  global $total, $asset_type, $asset_children_type, $data, $changed, $o, $cron;
  if ($_POST['action'] == 'edit' || $cron) {
    $copy = $client->copy ( array ('authentication' => $auth, 'identifier' => array('type' => 'block_XHTML_DATADEFINITION', 'id' => 'e05452df7f000002357063689eb57431'), 'copyParameters' => array('newName'=> $asset['name'], 'destinationContainerIdentifier' => array('id' =>'e0550c0a7f00000235706368275431ca', type => 'folder'), 'doWorkflow'=>false) ) );
    if ($copy->copyReturn->success == 'true') {
      
      if ($cron) {
        $o[3] .= '<div>Info Block Copy success for '.$asset['name'].'</div>';
      } else {
        echo '<div class="s">Info Block Copy success</div>';
      }
      
      $total['s']++;

      $folder = $client->read ( array ('authentication' => $auth, 'identifier' => array ('type' => 'folder', 'id' => 'e0550c0a7f00000235706368275431ca' ) ) );
      if ($folder->readReturn->success == 'true') {
        $children = ( array ) $folder->readReturn->asset->folder;
        $blockID = '';
        foreach($children["children"]->child as $child) {
          if ($child->path->path == 'faculty-blocks/'.$asset['name']) {
            $blockID = $child->id;
          }
        }
      } else {
        
        if ($cron) {
          $o[1] .= '<div style="padding:3px;color:#fff;background:#c00;">Failed to read info blocks folder</div>';
        } else {
          echo '<div class="f">Failed to read info blocks folder</div>';
        }
        
      }
    } else {
      
      if ($cron) {
        $o[1] .= '<div style="padding:3px;color:#fff;background:#c00;">Info Block Copy failed: '.$asset['path'].'</div>';
      } else {
        $result = $client->__getLastResponse();
        echo '<div class="f">Info Block Copy failed: '.$asset['path'].'<div>'.htmlspecialchars(extractMessage($result)).'</div></div>';
      }
      
      $total['f']++;
    }
  }

  changes($asset, $blockID);

  if ($_POST['after'] == 'on' && !$cron) {
    echo '<button class="btn" href="#aModal'.$asset['id'].'" data-toggle="modal">View After</button><div id="aModal'.$asset['id'].'" class="modal hide" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-body">';
      print_r($asset); // Shows the page as it will be
    echo '</div></div>';
  }

  if ($changed == true) {
    if ($_POST['action'] == 'edit' || $cron) {
      $edit = $client->edit ( array ('authentication' => $auth, 'asset' => array($asset_children_type => $asset) ) );
    }
    if ($edit->editReturn->success == 'true') {
      if ($cron) {
        $o[2] .= '<div style="color:#090;">Edit success: <a href="https://cms.slc.edu:8443/entity/open.act?id='.$asset['id'].'&type='.$type.'#highlight">'.$asset['path']."</a></div>";
      } else {
        echo '<div class="s">Edit success</div>';
      }
      $total['s']++;

      $email = false;
      foreach ($asset["structuredData"]->structuredDataNodes->structuredDataNode as $sdnode) {
        if ($sdnode->identifier == "bio") {
          foreach ($sdnode->structuredDataNodes->structuredDataNode as $datanode) {
            if ($datanode->identifier == "email" && $datanode->text != '') {
              $email = $datanode->text;
            }
          }
        }
      }

      if ($email) {
        $blockAsset = array ('type' => 'block_XHTML_DATADEFINITION', 'id' => $blockID );
        $reply = $client->readAccessRights ( array ('authentication' => $auth, 'identifier' => $blockAsset ) );
        if ($reply->readAccessRightsReturn->success == 'true') {
          $accessRightsInformation = $reply->readAccessRightsReturn->accessRightsInformation;
  
          $accessToAdd = array('level' => 'write', 'type' => 'user', 'name' => $email);

          if (!is_array($accessRightsInformation->aclEntries->aclEntry))
            $accessRightsInformation->aclEntries->aclEntry=array($accessRightsInformation->aclEntries->aclEntry);
          array_push($accessRightsInformation->aclEntries->aclEntry, $accessToAdd);

          $editAccess = $client->editAccessRights ( array ('authentication' => $auth, 'accessRightsInformation' => $accessRightsInformation, 'applyToChildren' => false ) );
          if ($editAccess->editAccessRightsReturn->success == 'true') {
            if ($cron) {
              $o[2] .= '<div style="color:#090;">Edit rights success: <a href="https://cms.slc.edu:8443/entity/open.act?id='.$blockID.'&type=block_XHTML_DATADEFINITION#highlight">'.$asset['path']."</a></div>";
            } else {
              echo '<div class="s">Edit rights success</div>';
            }
            
            $total['s']++;
          } else {
            
            if ($cron) {
              $o[1] .= '<div style="padding:3px;color:#fff;background:#c00;">Edit rights failed: '.$asset['path'].'</div>';
            } else {
              $result = $client->__getLastResponse();
              echo '<div class="f">Edit rights failed: '.$asset['path'].'<div>'.extractMessage($result).'</div></div>';
            }
            
            $total['f']++;
          }

        } else {
          if ($cron) {
            $o[1] .= '<div style="padding:3px;color:#fff;background:#c00;">Access Read failed</div>';
          } else {
            echo '<div class="f">Access Read failed</div>';
          }
          
        }
      } else {  
        if ($cron) {
          $o[1] .= '<div>No email, no access!</div>';
        } else {
          echo 'No email, no access!';
        }
      }

    } else {
      if ($_POST['debug'] == 'on') {
        $result = $client->__getLastResponse();
      }
      if ($cron) {
        $o[1] .= '<div style="padding:3px;color:#fff;background:#c00;">Edit failed: <a href="https://cms.slc.edu:8443/entity/open.act?id='.$asset['id'].'&type='.$type.'#highlight">'.$asset['path']."</a><div>".htmlspecialchars(extractMessage($result)).'</div></div>';
      } else {
        echo '<div class="f">Edit failed: '.$asset['path'].'<div>'.htmlspecialchars(extractMessage($result)).'</div></div>';
      }
      $total['f']++;
    }
  } else {
    if (!$cron) {echo '<div class="k">No changes needed</div>';}
    $total['k']++;
  }
}



?>
