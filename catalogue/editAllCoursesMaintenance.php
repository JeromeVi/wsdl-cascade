<?php
$title = 'Copying DD data to metadata for all course pages in specified years';

$start_asset = '817373157f00000101f92de5bea1554a';

/*
 *  The following pagetest and foldertest match either the current years, 
 *  or the archived years.
 *  To change from one to the other, comment and uncomment the appropriate
 *  lines in BOTH pagetest and foldertest. Also, adjust the $year folder param.
 *  If you don't want to create references, uncomment the line ~110 that is continue;
 *  Finally, adjust around line 100 which includes !strpos( $asset['path'], '/_archived/' )
 *  If you want to narrow down pages editing, add a course name of the end of 
 *  pagetest e.g. ...primary\/[a-z]allet/'
 */

// $year = '[-0-9]+'; // Matches all years
$year = '2016-2017';


function pagetest($child) {
  global $year;
  // if (preg_match('/^[a-z][-a-z\/]+\/_archived\/'.$year.'\/[a-zA-Z0-9]/',$child->path->path))
  if (preg_match('/^[a-z][-a-z\/]+\/'.$year.'\/[a-zA-Z0-9]/',$child->path->path))
    return true;
}
function foldertest($child) {
  global $year;
  // if (preg_match('/^[a-z][-a-z\/]+$/',$child->path->path) || preg_match('/^[a-z][-a-z\/]+\/_archived$/',$child->path->path) || preg_match('/^[a-z][-a-z\/]+\/_archived\/'.$year.'$/',$child->path->path) )
  if (preg_match('/^[a-z][-a-z\/]+$/',$child->path->path) || preg_match('/^[a-z][-a-z\/]+\/'.$year.'$/',$child->path->path) )
    return true;
}
function edittest($asset) {
  if (preg_match('/^slc-catalogue-undergraduate\/Disicipline Course Pages/', $asset["contentTypePath"]))
    return true;
}

function changes(&$asset) {
  global $changed, $total, $discNames, $relatedIDs, $year, $auth, $client, $cron, $o, $disc_folder, $disc_name;
  $changed = false;
  $newTitle = trim($asset['metadata']->title);
  $newTitle = preg_replace('/& /','and ',$newTitle);
  $newTitle = preg_replace('/&amp; /','and ',$newTitle);
  $newTitle = preg_replace('/< /','&lt; ',$newTitle);
  if ($asset["metadata"]->title != $newTitle) {
    $changed = true;
    $asset['metadata']->title = $newTitle;
  }
  if ( isset($disc_folder) ) {
    foreach ($asset["metadata"]->dynamicFields->dynamicField as $dyn) {
      if ($dyn->name == "discipline-folder") {
        if ($dyn->fieldValues->fieldValue->value !== $disc_folder) {
          $dyn->fieldValues->fieldValue->value = $disc_folder;
          $changed = true;
        }
      }
      if ($dyn->name == "discipline-name") {
        if ($dyn->fieldValues->fieldValue->value !== $disc_name) {
          $dyn->fieldValues->fieldValue->value = $disc_name;
          $changed = true;
        }
      }
    }
  }
  foreach ($asset["structuredData"]->structuredDataNodes->structuredDataNode as $field) {
    if ( $field->identifier == 'related' && !strpos( $asset['path'], '/_archived/' ) ) {
    // } elseif ( $field->identifier == 'related' ) {
      // This code can be used to update the courses relationships when a discipline is renamed:
      // if ($field->text == 'Visual Arts') {
      //   $field->text = 'Visual and Studio Arts';
      //   $changed = true;
      // }
      // echo $field->text;

      // If editing archived years, you may wish to not create references. If so, uncomment this line:
      // continue;
      $disc = $field->text;
      $discFolder = $discNames[$disc];
      if ($discFolder) {
        if ($relatedIDs[$discFolder]) {
        
          $reference = array(
            'reference' => array(
              'name' => $asset['name'],
              'parentFolderId' => $relatedIDs[$discFolder],
              'referencedAssetId' => $asset['id'],
              'siteName' => 'www.sarahlawrence.edu+catalogue',
              'referencedAssetType' => 'page'
            )
          );

          $read = $client->read(array ('authentication' => $auth, 'identifier' => array('id'=>$relatedIDs[$discFolder], 'type' => 'folder') ) );
          if ($read->readReturn->success === 'true') {
            $dest = ( array ) $read->readReturn->asset->folder;
            if ($_POST['children'] == 'on' && !$cron) {
              echo '<button class="btn" href="#cModal'.$dest['id'].'" data-toggle="modal">View Children</button><div id="cModal'.$dest['id'].'" class="modal hide" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-body">';
                print_r($dest["children"]); // Shows all the children of the folder
              echo '</div></div>';
            }
            $matchNew = false;
            
            $children_array = is_object($dest["children"]->child) ? array($dest["children"]->child) : $dest["children"]->child;
            
            foreach ($children_array as $key=>$existingRef) {
              if (strcmp(basename($existingRef->path->path), $asset['name']) === 0) {
                $matchNew = true;
                if (!$cron) {echo '<div class="k">A reference for '.$asset['name'].' already exists in <strong>'.$dest['path'].'</strong>.</div>';}
              }
            }

            if (!$matchNew) {
              $matchNew = false;
              $pathFolders = explode('/',$asset['path']);
              if ($pathFolders[0] == $discFolder) {
                if (!$cron) {echo "<div class='f'>Ooopsie, this course is trying to be related to its own discipline.</div>";}
              } else {
                if (!$cron) {echo "<div>A reference for ".$asset['name']." will be created in $discFolder/$year/related/ :</div>";}

                // Create the new reference
                if ($_POST['action'] == 'edit' || $cron) {
                  $create = $client->create(array ('authentication' => $auth, 'asset' => $reference) );
                }
                if ($create->createReturn->success === 'true') {
                  if ($cron) {
                    $o[0] .= '<div style="color:#090;">A reference was created for <a href="https://cms.slc.edu:8443/entity/open.act?id='.$asset['id'].'&type='.$type.'#highlight">'.$asset['name']."</a> in $discFolder/$year/related/</div>";
                  } else {
                    echo '<div class="s">Creation success: '.$asset['name'].' in '.$discFolder.'</div>';
                  }
                  $total['s']++;
                } else {
                  if ($_POST['action'] == 'edit') {$result = $client->__getLastResponse();} else {$result = '';}
                  if ($cron) {
                    $o[1] .= '<div style="padding:3px;color:#fff;background:#c00;">Creation of a reference failed for <a href="https://cms.slc.edu:8443/entity/open.act?id='.$asset['id'].'&type=page#highlight">'.$asset['path']."</a><div>".htmlspecialchars(extractMessage($result)).'</div></div>';
                  } else {
                    echo '<div class="f">Creation Failed: '.$asset['name'].' in '.$discFolder.'<div>'.htmlspecialchars(extractMessage($result)).'</div></div>';
                  }
                  $total['f']++;
                }
              }
            }
              
          } else {
            if ($cron) {
              $o[1] .= '<div style="padding:3px;color:#fff;background:#c00;">Failed to read folder: '.$asset["path"].'</div>';
            } else {
              echo '<div class="f">Failed to read folder: '.$discFolder.'</div>';
            }
          }
        } else {  
          if ($cron) {
            $o[1] .= '<div style="padding:3px;color:#fff;background:#c00;">Discipline related folder ID does not exist: '.$disc.'</div>';
          } else {
            echo '<div class="f">Discipline related folder ID does not exist: '.$disc.'</div>';
          }
        }
      } else {
        if ($disc !== '' && $disc !== 'Global Studies' && $disc !== 'Science, Technology, and Society') {
          $total['f']++;
        }
        if ($disc !== '') {
          if ($cron) {
            $o[1] .= '<div style="padding:3px;color:#fff;background:#c00;">Related Discipline does not exist: '.$disc.'</div>';
          } else {
            echo '<div class="f">Related Discipline does not exist: '.$disc.'</div>';
          }
        }
      }
    }
  }
}

include('relatedIDs.php');


if (!$cron) {
  include('../html_header.php');
}



function readFolder($client, $auth, $id) {
  global $asset_type, $asset_children_type, $data, $o, $cron, $disc_folder, $disc_name;
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
    if ( !strpos($asset['path'], '/') ) {
      $disc_name = $asset['metadata']->displayName;
      $disc_folder = $asset['path'];
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
  global $asset_type, $asset_children_type, $data, $o, $cron, $total;
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
  
  changes($asset);
  
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
        $o[2] .= '<div style="color:#090;">Edit success: <a href="https://cms.slc.edu:8443/entity/open.act?id='.$asset['id'].'&type='.$asset_children_type.'#highlight">'.$asset['path']."</a></div>";
      } else {
        echo '<div class="s">Edit success</div>';
      }
      $total['s']++;
    } else {
      if ($_POST['debug'] == 'on' || $cron) {
        $result = $client->__getLastResponse();
      }
      if ($cron) {
        $o[1] .= '<div style="padding:3px;color:#fff;background:#c00;">Edit failed: <a href="https://cms.slc.edu:8443/entity/open.act?id='.$asset['id'].'&type='.$asset_children_type.'#highlight">'.$asset['path']."</a><div>".htmlspecialchars(extractMessage($result)).'</div></div>';
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