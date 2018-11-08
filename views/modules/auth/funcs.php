<?php

function isNull(/* variables*/) {

	if (
			strlen(trim(/*variable formato $variable*/)) < 1 ||
			strlen(trim(/*variable formato $variable*/)) < 1 ) {
		return true;
	} else {
		return false;
	}
}

function isEmail($email) {
	if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return true;
	} else {
		return false;
	}
}

function validaPassword($var1, $var2) {
	if (strcmp($var1, $var2) !== 0) {
		return false;
	} else {
		return true;
	}
}

function minMax($min, $max, $valor) {
	if (strlen(trim($valor)) < $min) {
		return true;
	} else if (strlen(trim($valor)) > $max) {
		return true;
	} else {
		return false;
	}
}

function usuarioExiste($nom_usu) {
	global $mysqli;

	$stmt = $mysqli->prepare("SELECT cve_usu FROM usuario WHERE nom_usu = ? LIMIT 1");
	$stmt->bind_param("s", $nom_usu);
	$stmt->execute();
	$stmt->store_result();
	$num = $stmt->num_rows;
	$stmt->close();

	if ($num > 0) {
		return true;
	} else {
		return false;
	}
}

function emailExiste($email) {
	global $mysqli;

	$stmt = $mysqli->prepare("SELECT cve_usu FROM usuario WHERE correo_usu = ? LIMIT 1");
	$stmt->bind_param("s", $email);
	$stmt->execute();
	$stmt->store_result();
	$num = $stmt->num_rows;
	$stmt->close();

	if ($num > 0) {
		return true;
	} else {
		return false;
	}
}

function generateToken() {
	$gen = md5(uniqid(mt_rand(), false));
	return $gen;
}

function hashPassword($password) {
	$hash = password_hash($password, PASSWORD_DEFAULT);
	return $hash;
}

function resultBlock($errors) {
	if (count($errors) > 0) {
		echo "<div id='error' class='alert alert-danger' role='alert'>
			<a href='#' onclick=\"showHide('error');\">[X]</a>
			<ul>";
		foreach ($errors as $error) {
			echo "<li>" . $error . "</li>";
		}
		echo "</ul>";
		echo "</div>";
	}
}

function registraUsuario2($usuario, $pass_hash, $nombre, $email, $activo, $token, $tipo_usuario) {

	global $mysqli;

	$stmt = $mysqli->prepare("INSERT INTO usuarios (usuario, password, nombre, correo, activacion, token, id_tipo) VALUES(?,?,?,?,?,?,?)");
	$stmt->bind_param('ssssisi', $usuario, $pass_hash, $nombre, $email, $activo, $token, $tipo_usuario);

	if ($stmt->execute()) {
		return $mysqli->insert_id;
	} else {
		return 0;
	}
}

function obtenerCVE_USU($email) {
	global $mysqli;

	$stmt = $mysqli->prepare("SELECT cve_usu FROM usuario WHERE correo_usu = ? LIMIT 1");
	$stmt->bind_param("s", $email);
	$stmt->execute();
	$stmt->bind_result($clave_usu);
	$stmt->fetch();
	return $clave_usu;
}

function registraUsuario($nom_per, $ap_per, $am_per, $fn_per, $tel_per, $direccion, $lat, $lng, $tipo, $correo, $nom_usu, $pass_hash, $cb, $vlic, $tipo_usuario) {

	global $mysqli;
	if (!($stmt = $mysqli->prepare("CALL ps_registroUsuario(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"))) {
		echo "Falló la preparación: (" . $mysqli->errno . ") " . $mysqli->error;
	} else {

		$stmt->bind_param('ssssisddssssssi', $nom_per, $ap_per, $am_per, $fn_per, $tel_per, $direccion, $lat, $lng, $tipo, $correo, $nom_usu, $pass_hash, $cb, $vlic, $tipo_usuario);
		if ($stmt->execute()) {
			return obtenerCVE_USU($correo);
		} else {
			echo "Falló la ejecución: (" . $stmt->errno . ") " . $stmt->error;
			return 0;
		}
	}
}

function enviarEmail($email, $nombre, $asunto, $cuerpo) {

	require_once 'PHPMailer/PHPMailerAutoload.php';

	$mail = new PHPMailer();
	$mail->isSMTP();
	$mail->SMTPAuth = true;
	$mail->SMTPSecure = 'tipo de seguridad';
	$mail->Host = 'smtp.hosting.com';
	$mail->Port = 'puerto';

	$mail->Username = 'miemail@dominio.com';
	$mail->Password = 'password';

	$mail->setFrom('miemail@dominio.com', 'Sistema de Usuarios');
	$mail->addAddress($email, $nombre);

	$mail->Subject = $asunto;
	$mail->Body = $cuerpo;
	$mail->IsHTML(true);

	if ($mail->send())
		return true;
	else
		return false;
}

function validaIdToken($id, $token) {
	global $mysqli;

	$stmt = $mysqli->prepare("SELECT activacion_usu FROM usuario WHERE cve_usu = ? LIMIT 1");
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$stmt->store_result();
	$rows = $stmt->num_rows;

	if ($rows > 0) {
		$stmt->bind_result($activacion);
		$stmt->fetch();

		if ($activacion == 1) {
			$msg = "La cuenta ya se activo anteriormente.";
		} else {
			if (activarUsuario($id)) {
				$msg = 'Cuenta activada.';
			} else {
				$msg = 'Error al Activar Cuenta';
			}
		}
	} else {
		$msg = 'No existe el registro para activar.';
	}
	return $msg;
}

function activarUsuario($id) {
	global $mysqli;

	$stmt = $mysqli->prepare("UPDATE usuario SET activacion_usu=1 WHERE cve_usu = ?");
	$stmt->bind_param('s', $id);
	$result = $stmt->execute();
	$stmt->close();
	return $result;
}

function isNullLogin($usuario, $password) {
	if (strlen(trim($usuario)) < 1 || strlen(trim($password)) < 1) {
		return true;
	} else {
		return false;
	}
}

function login($usuario, $password) {
	global $mysqli;

	$stmt = $mysqli->prepare("SELECT cve_usu, tipo_usu, pass_usu FROM usuario WHERE nom_usu = ? || correo_usu = ? LIMIT 1");
	$stmt->bind_param("ss", $usuario, $usuario);
	$stmt->execute();
	$stmt->store_result();
	$rows = $stmt->num_rows;

	if ($rows > 0) {

		if (isActivo($usuario)) {

			$stmt->bind_result($id, $id_tipo, $passwd);
			$stmt->fetch();

			$validaPassw = password_verify($password, $passwd);

			if ($validaPassw) {

				lastSession($id);
				$_SESSION['id_usuario'] = $id;
				$_SESSION['tipo_usuario'] = $id_tipo;

				header("location: ../vistaPrincipal/indexP.php");
			} else {

				$errors = "La contrase&ntilde;a es incorrecta";
			}
		} else {
			$errors = 'El usuario no esta activo';
		}
	} else {
		$errors = "El nombre de usuario o correo electr&oacute;nico no existe";
	}
	return $errors;
}

function lastSession($id) {
	global $mysqli;

	$stmt = $mysqli->prepare("UPDATE usuario SET usesion_usu=NOW() WHERE cve_usu = ?");
	$stmt->bind_param('s', $id);
	$stmt->execute();
	$stmt->close();
}

function isActivo($usuario) {
	global $mysqli;

	$stmt = $mysqli->prepare("SELECT activacion_usu FROM usuario WHERE nom_usu = ? || correo_usu = ? LIMIT 1");
	$stmt->bind_param('ss', $usuario, $usuario);
	$stmt->execute();
	$stmt->bind_result($activacion);
	$stmt->fetch();

	if ($activacion == 1) {
		return true;
	} else {
		return false;
	}
}

function generaTokenPass($user_id) {
	global $mysqli;

	$token = generateToken();

	$stmt = $mysqli->prepare("UPDATE usuarios SET token_password=?, password_request=1 WHERE id = ?");
	$stmt->bind_param('ss', $token, $user_id);
	$stmt->execute();
	$stmt->close();

	return $token;
}

function getValor($campo, $tabla, $campoWhere, $valor) {
	global $mysqli;

	$stmt = $mysqli->prepare("SELECT $campo FROM $tabla WHERE $campoWhere = ? LIMIT 1");
	$stmt->bind_param('s', $valor);
	$stmt->execute();
	$stmt->store_result();
	$num = $stmt->num_rows;

	if ($num > 0) {
		$stmt->bind_result($_campo);
		$stmt->fetch();
		return $_campo;
	} else {
		return null;
	}
}

function getPasswordRequest($id) {
	global $mysqli;

	$stmt = $mysqli->prepare("SELECT password_request FROM usuarios WHERE id = ?");
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$stmt->bind_result($_id);
	$stmt->fetch();

	if ($_id == 1) {
		return true;
	} else {
		return null;
	}
}

function verificaTokenPass($user_id, $token) {

	global $mysqli;

	$stmt = $mysqli->prepare("SELECT activacion FROM usuarios WHERE id = ? AND token_password = ? AND password_request = 1 LIMIT 1");
	$stmt->bind_param('is', $user_id, $token);
	$stmt->execute();
	$stmt->store_result();
	$num = $stmt->num_rows;

	if ($num > 0) {
		$stmt->bind_result($activacion);
		$stmt->fetch();
		if ($activacion == 1) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function cambiaPassword($password, $user_id, $token) {

	global $mysqli;

	$stmt = $mysqli->prepare("UPDATE usuarios SET password = ?, token_password='', password_request=0 WHERE id = ? AND token_password = ?");
	$stmt->bind_param('sis', $password, $user_id, $token);

	if ($stmt->execute()) {
		return true;
	} else {
		return false;
	}
}
