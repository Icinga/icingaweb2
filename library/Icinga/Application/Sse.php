<?php


$msg = strip_tags($_GET['msg']);
$role = strip_tags($_GET['role']);
$events_file = '/var/cache/httpd/events.txt';
$interval = 5;

function newMsg($events_file, $id, $msg, $event) {

  $event = "id: $id" . PHP_EOL . "event: " . $event . PHP_EOL . "data: $msg" . PHP_EOL;
  $fp = fopen($events_file, 'a');  
  fwrite($fp, $event . PHP_EOL);  
  fclose($fp);

  echo "Sent the following message successfuly: " .PHP_EOL;
  echo $event;

}

function readMsg($file_data) {
    global $lastId;

    $lines = explode(PHP_EOL, $file_data);
    $isNew = False;
    foreach($lines as $line) {
       if(strpos($line, 'id:') === 0) {
          $id = intval(explode(" ", $line)[1]);
          if ($id > $lastId) {
             $isNew = True;
             $lastId = $id;
          }
       }

       if ($isNew) {
         echo $line . PHP_EOL;
       }

       if ($isNew && $line == '') $isNew = False;
    }

  ob_flush();
  flush();
 
}

if(!empty($msg) && !empty($role)){

  newMsg($events_file, time(), $msg, $role);

} else {

  //creating Event stream 
  header('Content-Type: text/event-stream');
  header('Cache-Control: no-cache');
  header("Connection: keep-alive");

  $lastId = time();
  $last_file_crc32 = 0;

  while (True) {
    $file_data = file_get_contents($events_file);
    $file_crc32 = crc32($file_data);

    if($file_crc32 != $last_file_crc32) {
      $last_file_crc32 = $file_crc32;

      // file changed, parse data
      readMsg($file_data);
    }
    sleep($interval);
  }

}

?>
