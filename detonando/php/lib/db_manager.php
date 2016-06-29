<?php
class DBManager {
	private $db_user	= 'detonando';
	private $db_pwd		= 'detonando';
	private $db_name	= 'detonando';
	private $db_url		= 'localhost';
	private $images_url = 'img_usr';
	private $conn;
	function __construct(){
		if(!is_dir($this->images_url)){
			mkdir($this->images_url, 0777);
		}
		
		$this->conn= new mysqli(
				$this->db_url, 
				$this->db_user, 
				$this->db_pwd,
				$this->db_name
		);
		$this->conn->set_charset('utf8');
		// Check connection
		if ($this->conn->errno) {
		    throw new Exception("Connection failed: " . $this->conn->error);
		}
		
	}
	
	
	
	function loginKiosco($kioscoId,$llave){
		$sql = "SELECT isAdmin FROM kiosco WHERE id = ? AND llave = MD5(?)";
		$prepareQ = $this->conn->prepare($sql);
		
		$prepareQ->bind_param('ss',$kioscoId,$llave);
		$prepareQ->bind_result($isAdmin);
		$prepareQ->execute();
		
		$tmpRes = $prepareQ->fetch();
		if($tmpRes === FALSE) {
			$errorMsg =	sprintf("Falló la conexión: %s\n", $this->conn->error);
			throw new Exception($errorMsg , 6);
		}
		
		if($tmpRes === NULL) {
			$errorMsg =	sprintf("ID o llave del kiosco incorrecto : %s\n", $kioscoId);
			throw new Exception($errorMsg , 1);
		}
		
		return $isAdmin;
	}
	
	
	/**
	 * 
	 * @param unknown $userID
	 * @throws Exception
	 * @return multitype:unknown
	 */
	public function getUserData($userID){
		$sql = "SELECT nombre, ap_paterno, ap_materno, saldo, tablet_id from user WHERE id = ?";
		$prepareQ = $this->conn->prepare($sql);
		$prepareQ->bind_param('s',$userID);
		$prepareQ->execute();
		$prepareQ->bind_result($nombre, $apPaterno, $apMaterno, $saldo, $tableID);
		$result = null;
		
		$tmpRes = $prepareQ->fetch();
		if($tmpRes === FALSE) {
			$errorMsg =	sprintf("Falló la conexión: %s\n", $this->conn->error);
			throw new Exception($errorMsg , 6);
		}
		
		if($tmpRes === NULL) {
			$errorMsg =	sprintf("No se encontró el usuario con ID : %s\n", $userID);
			throw new Exception($errorMsg , 2);
		}
		
		$result =  array(
				'nombre' => $nombre,
				'ap_paterno' => $apPaterno,
				'ap_materno' => $apMaterno,
				'saldo' => $saldo,
				'table' => $tableID,
				'img_url' => "images/$userID.jpg"
		);
		$prepareQ->close();
		
		$sql = "SELECT * FROM familiares WHERE user_id = ? ";
		$prepareQ = $this->conn->prepare($sql);
		$prepareQ->bind_param('s',$userID);
		$prepareQ->execute();
		$prepareQ->bind_result($uid, $nombre, $parentesco,$sexo);
		
		$result['familiares'] =  array();
		while($prepareQ->fetch()){
			$result['familiares'][] = array(
					'nombre' => $nombre,
					'parentesco' => $parentesco,
					'sexo' => $sexo
			);	
		}
		$prepareQ->close();
		return $result;
	}
	
	/**
	 * 
	 * @param unknown $userID
	 * @param unknown $userPass
	 */
	function getSaldo($userID,$userPass){
		$noPass = $userPass === false;
		$sql = "SELECT nombre, ap_paterno, ap_materno, saldo from user WHERE id = ?";
		if($noPass)
			$sql .= " AND password = ''";
		else 
			$sql .= " AND password = MD5(?)";
		$prepareQ = $this->conn->prepare($sql);
		
		if($noPass)
			$prepareQ->bind_param('s',$userID);
		else 
			$prepareQ->bind_param('ss',$userID,$userPass);
		
		$prepareQ->execute();
		$prepareQ->bind_result($nombre, $apPaterno, $apMaterno, $saldo);
		$result = null;
		
		$tmpRes = $prepareQ->fetch();
		if($tmpRes === FALSE) {
			$errorMsg =	sprintf("Falló la conexión: [%s]\n", $this->conn->error);
			throw new Exception($errorMsg , 6);
		}
		
		if($tmpRes === NULL) {
			$errorMsg =	sprintf("ID o Contraseña del usuario incorrecto : %s\n", $userID);
			throw new Exception($errorMsg , 2);
		}
		$result =  array(
				'nombre' => $nombre,
				'ap_paterno' => $apPaterno,
				'ap_materno' => $apMaterno,
				'saldo' => $saldo,
		);
		$prepareQ->close();
		
		return $result;
	}
	
	/**
	 * 
	 * @param unknown $userID
	 * @param unknown $userPass
	 * @param unknown $user2ID
	 * @param unknown $amount
	 * @param unknown $subject
	 * @param unknown $kioscoID
	 * @throws Exception
	 */
	function addTransaction($userID,$userPass,$user2ID, $amount, $subject, $kioscoID){
		$tryUsr2 = false;
		try{
			$usr = $this->getSaldo($userID,$userPass);
			
			if($usr['saldo'] < $amount){
				throw new Exception('Saldo insuficiente en la cuenta del usuario emisor',4);
			}
			
			$tryUsr2 = true;
			$usr2 = $this->getUserData($user2ID);
			$tryUsr2 = false;
			
			$sql1 = "UPDATE user SET saldo = saldo - ? WHERE id = ?;\n";
			$sql2 = "UPDATE user SET saldo = saldo + ? WHERE id = ?;\n";
			$sql3 = "INSERT INTO transacciones (concepto,monto,kiosco_id,emisor_id,receptor_id) VALUES (?,?,?,?,?);";
			
			$prepareQ1 = $this->conn->prepare($sql1);
			$prepareQ2 = $this->conn->prepare($sql2);
			$prepareQ3 = $this->conn->prepare($sql3);
			
			if ($this->conn->errno) {
				throw new Exception("Connection failed: " . $this->conn->error);
			}
			
			$prepareQ1->bind_param('is',$amount,$userID);
			$prepareQ2->bind_param('is', $amount,$user2ID);
			$prepareQ3->bind_param('sisss',$subject,$amount,$kioscoID,$userID,$user2ID);
			
			$this->conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
			$correct = 
				$prepareQ1->execute() &&
				$prepareQ2->execute() &&
				$prepareQ3->execute();
			
			if($correct){
				$this->conn->commit();
			}
			$prepareQ1->close();
			$prepareQ2->close();
			$prepareQ3->close();
			
			if(!$correct){
				$errorMsg =	sprintf("Error en la operación: %s\n", $this->conn->error);
				throw new Exception($errorMsg , 6);
			}
			
			return true;
		}catch (Exception $e){
			if($tryUsr2)
				throw  new Exception('ID del usuario Receptor incorrecto',3);	
			throw $e;
		}
	}
	
	
	
	/**
	 * 
	 * @param unknown $userID
	 * @param unknown $userPass
	 * @throws Exception
	 * @return multitype:unknown
	 */
	function getOperaciones($userID,$userPass,$initDate = false, $endDate = false){
		
		try{
			$this->getSaldo($userID,$userPass);
			$sql = "SELECT \n"
			    . " t.kiosco_id,\n"
			    . " t.fecha,\n"
			    . " IF(t.emisor_id = ? ,'egreso','ingreso') as tipo ,\n"
			    . " IF(t.emisor_id = ? ,t.receptor_id,t.emisor_id) as usuario, \n"
			    . " (SELECT CONCAT_WS('-@-',u.nombre,u.ap_materno,u.ap_materno) FROM user as u WHERE id = IF(t.emisor_id = ?,t.receptor_id,t.emisor_id)) as user_txt\n"
			    . "FROM \n"
			    . " transacciones as t\n"
			    . "WHERE \n"
			    . " (t.emisor_id = ? OR \n"
			    . " t.receptor_id = ?) ";
			
			
			if($initDate !== false){
				$initDate = DateTime::createFromFormat('d/m/Y', $initDate);
				$endDate = DateTime::createFromFormat('d/m/Y', $endDate);
				
				if($initDate === false  || $endDate === false)
					throw  new Exception("Las fechas no estan en formato adecuado dd/mm/YYYY [$initDate]-[$endDate]",11);
				
				$sql .= "AND (t.fecha BETWEEN ? AND ?)";
			} else {
				$sql .= "LIMIT 0,10 ORDER BY t.fecha DESC";
			}
			
			
			$prepareQ = $this->conn->prepare($sql);
			if($prepareQ === FALSE){
				$errorMsg =	sprintf("Falló la conexión: %s\n", $this->conn->error);
				throw new Exception($errorMsg , 6);
			}
			
			
			if($initDate !== false){
				$strInitD	= $initDate->format('Y-m-d').' 00:00:00';
				$strEndD	= $endDate->format('Y-m-d').' 23:59:59';
				$prepareQ->bind_param('sssssss',$userID,$userID,$userID,$userID,$userID,$strInitD,$strEndD);
			}else{
				$prepareQ->bind_param('sssss',$userID,$userID,$userID,$userID,$userID);
			}
			
			$prepareQ->execute();
			$prepareQ->bind_result($kioscoID, $fecha, $tipo, $usuario, $usrData);
			$result = null;
			
			$result = array();
			while($tmpRes = $prepareQ->fetch()){	
				if($tmpRes === FALSE) {
					$errorMsg =	sprintf("Falló la conexión: [%s]\n", $this->conn->error);
					throw new Exception($errorMsg , 6);
				}
				
				$tmpUsrData = explode('-@-', $usrData);
				$tmpTrans = array(
					'kiosco'	=> $kioscoID,
					'tipo'		=> $tipo,
					'fecha'		=> $fecha,
					'usuario'	=> array(
						'id'			=> $usuario,
						'nombre'		=> $tmpUsrData[0],
						'ap_paterno'	=> $tmpUsrData[1],
						'ap_paterno'	=> $tmpUsrData[2],
						'img_url' 		=> "images/$usuario.jpg"
					),
				 );
				 $result[] = $tmpTrans;
			}
			$prepareQ->close();
			return $result;
		}catch(Exception $e){
			throw $e;
		}
	}
}
?>