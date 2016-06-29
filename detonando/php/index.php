<?php
doResponse();


/**
 * 
 */
function doResponse(){
	$op = getRequestVar('op');
	$data = getRequestVar('data');
	$data = json_decode($data,true);
	include 'lib/db_manager.php';

	try{
		$DBManager = new DBManager();
		$response = array(
				'error' => array('code' => 0, 'msg' => ''),
		);

		revisaKiosco($data,$DBManager);
		
		switch ($op.''){
			case 'usuario':
				if(!isset($data['usuario']) || !isset($data['usuario']['id']))
					throw new Exception('ID del usuario incorrecto',2);
				
				$usrID = $data['usuario']['id'];
				$response['usuario'] = $DBManager->getUserData($usrID);
				break;
			case 'saldo':
				if(!isset($data['usuario']) || !isset($data['usuario']['id']))
					throw new Exception('ID del usuario incorrecto',2);
				
				$usrID = $data['usuario']['id'];
				$usrPass = isset($data['usuario']['password'])?$data['usuario']['password']:false;
				$response['usuario'] = $DBManager->getSaldo($usrID,$usrPass);
				break;
			case 'pago':
				if(!isset($data['emisor']) || !isset($data['emisor']['id']))
					throw new Exception('ID del usuario incorrecto',2);
				
				if(!isset($data['receptor']) || !isset($data['receptor']['id']))
					throw new Exception('ID del usuario incorrecto',2);
					
				if(!isset($data['transaccion']) || 
						!isset($data['transaccion']['monto'])|| 
						!isset($data['transaccion']['concepto'])||
						empty($data['transaccion']['concepto'].'') ||
						((int) preg_replace('/[^0-9]/', '', $data['transaccion']['monto'])) <= 0)
					throw new Exception('Formato de monto incorrecto o concepto incorrecto',5);
				
				$usrID		= $data['emisor']['id'];
				$usrPass	= isset($data['emisor']['password'])?$data['emisor']['password']:false;
				$usrID2		= $data['receptor']['id'];
				$monto		= $data['transaccion']['monto'];
				$concepto	= $data['transaccion']['concepto'];
				$kiosco		= $data['kiosco']['id'];
				$result = $DBManager->addTransaction($usrID,$usrPass,$usrID2,$monto,$concepto,$kiosco);
				if(!$result)
					throw new Exception('Error general no determinado',6);
					
				$response['error']['msg'] = 'Transacción exitosa';
				break;
			case 'operaciones':
				if(!isset($data['usuario']) || !isset($data['usuario']['id']))
					throw new Exception('ID del usuario incorrecto',2);
				

				$initD = false;
				$endD = false;
				if(isset($data['periodo'])){
					if(!isset($data['periodo']['ini']) || !isset($data['periodo']['end']))
						throw new Exception('Error en el formato de la fechas de periodo',11);
					$initD = $data['periodo']['ini'];
					$endD = $data['periodo']['end'];
				}
				
				$usrID = $data['usuario']['id'];
				$usrPass = isset($data['usuario']['password'])?$data['usuario']['password']:false;
				
				
				$response['transacciones'] = $DBManager->getOperaciones($usrID,$usrPass,$initD,$endD); 
				break;
			case 'modificar':
				break;
			case 'borrar':
				break;
			case 'password':
				break;
			default:
			throw new Exception("No se pudo determinar la operacion : $op",6);	 
		}
	
	} catch (Exception $e){
		error_log(print_r($e,true));
		$response['error']['code'] = $e->getCode();
		$response['error']['msg'] = $e->getMessage();
	}
	
	
	$salida = json_encode($response,JSON_PRETTY_PRINT);
	
	

	header('Content-Type: application/json; charset=utf-8');
	header('Content-Type: application/json; charset=utf-8');
	echo $salida;
}


/**
 * 
 * @param unknown $varName
 * @return Ambigous <number, unknown>
 */
function getRequestVar($varName){
	return isset($_REQUEST[$varName])?$_REQUEST[$varName]:0;
}

/**
 * 
 * @param unknown $data
 * @param unknown $DBManager
 * @throws Exception
 */
function revisaKiosco($data,$DBManager){
	$isKiosco = isset($data['kiosco'])&&isset($data['kiosco']['id'])&&(isset($data['kiosco']['key']));
	$idValid = FALSE;
	
	if($isKiosco)
		$idValid = $DBManager->loginKiosco($data['kiosco']['id'],$data['kiosco']['key']);
			
	if($idValid === FALSE)
		throw new Exception('Datos del kiosco invalido',1);
	
	return $idValid;
}
?>