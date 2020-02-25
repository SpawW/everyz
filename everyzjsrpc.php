<?php
/*
Original code from Zabbix 4.0 Source jsrpc.php with adaptations
**/
$basePath = str_replace('/local/app/views', '', dirname(__FILE__));
require_once $basePath . '/include/func.inc.php';
require_once $basePath . '/include/defines.inc.php';
require_once $basePath . '/include/classes/json/CJson.php';
require_once $basePath . '/include/classes/user/CWebUser.php';
require_once $basePath . '/include/classes/core/CHttpRequest.php';

$requestType = getRequest('type', PAGE_TYPE_JSON);
if ($requestType == PAGE_TYPE_JSON) {
  $http_request = new CHttpRequest();
  $json = new CJson();
  $data = $json->decode($http_request->body(), true);
} else {
  $data = $_REQUEST;
}

require_once $basePath . '/include/config.inc.php';

$page['title'] = 'RPC';
$page['file'] = 'jsrpc.php';
$page['type'] = detect_page_type($requestType);

require_once $basePath . '/include/page_header.php';

//|| ($requestType == PAGE_TYPE_JSON && (!isset($data['params']) || !is_array($data['params']))) 
if (!is_array($data) || !isset($data['method'])) {
  fatal_error('Wrong RPC call to JS RPC!');
}

$result = [];
switch ($data['method']) {
  case 'geoevents.get':
    // Filter by triggerID
    // $result = API::Trigger()->get([
    //   'output' => ['triggerid', 'description', 'priority', 'value'],
    //   // 'selectHosts' => ['hostid'],
    //   // 'active' => true,
    //   // 'only_true' => true,
    //   'expandComment' => true,
    //   'expandDescription' => true,
    //   'triggerids' => $data['triggerids'],
    //   'preservekeys' => true,
    //   'selectLastEvent' => true
    //   // ,      'filter' => ["value" => 1]
    //   // ,      'min_severity' => $severity  
    // ]);
    $result = API::Trigger()->get([
      'output' => [
        "triggerid",
        "description",
        "priority",
        "value"]
        , 'triggerids' => $data['triggerids']
      
    ]);
    // $result = $data['triggerids'];
    break;
  case 'host.triggers.get':
    if (!isset($data['hostid'])) {
      fatal_error('Missing hostid!');
    }
    $result = API::Trigger()->get([
      'output' => [
        "triggerid",
        "description",
        "priority"],
      'hostids' => [$data['hostid']]
    ]);
    // $result = $data;
    break;
  case 'host.inventory.get':
    $result = API::Host()->get([
      'startSearch' => true,
      'filter' => ['hostid' => $data['hostid']],
      'output' => ['hostid'],
      'selectInventory' => ["location_lat", "location_lon", "notes"],
      'sortfield' => 'name',
      'limit' => 15
    ]);
    break;
  case 'host.inventory.update':
    $params = ['hostid' => $data['hostid']];
    foreach ($data as $key => $value) {
      if (!(strpos($key, 'inventory_') === false)) {
        if (!isset($params['inventory'])) {
          $params['inventory'] = [];
        }
        $params['inventory'][str_replace('inventory_', '', $key)] = $value;
      }
    }
    //  var_dump([$params, $data]);
    $result = API::Host()->update($params);
    break;

  default:
    fatal_error('Wrong RPC parameters in JS RPC!');
}
$json = new CJson();

if ($requestType == PAGE_TYPE_TEXT_RETURN_JSON) {
  $json = new CJson();
  echo $json->encode([
    'jsonrpc' => '2.0',
    'result' => $result
  ]);
} elseif ($requestType == PAGE_TYPE_TEXT || $requestType == PAGE_TYPE_JS) {
  echo $result;
}

require_once $basePath . '/include/page_footer.php';
