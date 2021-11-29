<?php

define('DB_SERVER', 'localhost');
define('DB_DATABASE', 'geolocationapi');

// constantes para la instalacion en windows: 
define('DB_SERVER_USERNAME', 'root'); 
define('DB_SERVER_PASSWORD', ''); 

function insertar($tabla, $datosInsertar){

    $atributos = ""; // cadena auxilar para formatear los atributos a introducir en la consilta
    $valores = ""; // cadena auxilar para formatear los valores a introducir en la consilta

    $base_datos= new mysqli( DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE); // hago la conexón con la base de datos
    // si hay error en la conexión retorno el mensaje de error y salgo de la función
    if (mysqli_connect_errno()) {
        return sprintf("Falló la conexión con la base de datos: %s\n", mysqli_connect_error());
    }
    else{
        // formateo las 2 cadenas que continen los datos y los nombres de los atributos para luego hacer el insert
        foreach($datosInsertar as $clave => $valor){
            if(empty($atributos)){ // si es el primer valor lo incuyo 
                $atributos= sprintf( "%s",$clave);
                $valores= sprintf( "'%s'",$valor);
            }
            else{ // para el resto de valores copio el valor anterior de la variable y pongo una coma antes del anterior para que la sintaxis sea valida en la consulta
                $atributos= sprintf( "%s, %s",$atributos, $clave);
                $valores= sprintf( "%s, '%s'",$valores, $valor);
            }
            
        }
        //var_dump($atributos);
        //var_dump($valores);

        $sqlconsulta= "INSERT INTO ".$tabla." (".$atributos.") VALUES (".$valores.")"; // formateo la consulta
        //var_dump($sqlconsulta);
        
        // si la consulta no es valida se indica con un mensaje de error
        if($base_datos->query($sqlconsulta)==false){
            // si no se puede guardar el gasto en la base de datos
            $mensaje = "No se ha podido guardar el gasto en la base de datos";
        }
        else{
            $mensaje = NULL; // si es valida se guarda la referencia del nuevo usuario
        }
    }
}

function seleccionarTodos($tabla){
    $base_datos= new mysqli( DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE); // hago la conexón con la base de datos
    // inicio las variables
    $registro = array(); // array que va a contener los datos preparados para operar
    $temp = null; // variable auxlilar para guardar los datos en un array para luego devolverlo

    // si hay error en la conexión retorno el mensaje de error y salgo de la función
    if (mysqli_connect_errno()) {
        // si hay un error en la conexión se devuelve un mensaje al usuario
        return sprintf("Falló la conexión con la base de datos: %s\n", mysqli_connect_error());
    }
    else{
        $sqlConsulta= 'SELECT * FROM '.$tabla; // se le da formato a la consulta
        //var_dump($sqlConsulta);
        $consulta= $base_datos->query( $sqlConsulta); // se hace la consulta

        // guardo todos los resultados de la consulta en un array de arrays asociativos para luego usarlos en las páginas
        $i = 0; // variable auxilar usada como contador
        // bucle para leer los datos de la consulta leyendo entrada por entrada y guardandolo en un array
        do{ 
            $temp = $consulta->fetch_assoc(); // se guardan los datos de la entrada x en la variable temporal
            // si la variable contiene datos se guarda en el array de datos final
            if($temp!== null){
                $registro[$i]= $temp;
                $i++; // se aumenta el contador
            }
        }while ($temp !== null); // se repite hasta que ya no hay datos en el array temporal

        $registro['n-indices']=$i; // guardo el numero total de resultados en el array

        $consulta->free(); // libero la variable de la consulta
        if ($base_datos) $base_datos->close(); // cierro la conexión con la base de datos

        return $registro; // devuelvo los resultados
    }
}