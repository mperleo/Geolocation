<?php
    require_once 'baseDatos.php';

    /**
     * Función para eliminar los espacios y poner + para pasarlo a la petición con la API
     */
    function formatEspaces($cadena){
        $cadenaMod = str_replace(" ", "+", $cadena);
        return $cadenaMod;
    }

    function peticionCURL($url){
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_URL, $url);
        $response=curl_exec($handle);
        $respuestaJSON = json_decode($response, true);
        curl_close($handle);

        return $respuestaJSON;
    }

    $verLogs = false;

    $token = "Tu token";

    if(isset($_GET["direccion"]) && !empty($_GET["direccion"])){

        $mensajeDireccion = "<strong>Se ha indicado:</strong> <hr> Direccion: ".$_GET["direccion"]."<br>"."Cuidad: ".$_GET["ciudad"];

        $direccion=formatEspaces($_GET["direccion"]);
        $ciudad=formatEspaces($_GET["ciudad"]);
        
        $url_peticion="https://maps.googleapis.com/maps/api/geocode/json?address=".$direccion.",".$ciudad.",+Spain&language=es&country=spain&key=".$token;

        $respuestaApi = peticionCURL($url_peticion);

        // Si la respuesta es correcta
        if($respuestaApi["status"] == "OK"){
            //Mostramos los datos
            $latitud = $respuestaApi["results"]["0"]["geometry"]["location"]["lat"];
            $longitud =  $respuestaApi["results"]["0"]["geometry"]["location"]["lng"];

            $imgSrc="https://maps.googleapis.com/maps/api/staticmap?center=".$direccion.",".$ciudad."&zoom=20&size=1500x450&maptype=roadmap&markers=color:red%".$latitud.",".$longitud."&key=".$token;
        }
        else{
            $mensajeDireccion = "<strong>Se ha indicado:</strong> <hr> Direccion: ".$_GET["direccion"]."<br>"."Cuidad: ".$_GET["ciudad"]." <hr> Dirección no válida";
        }

        $atributos["direccion"]=$direccion;
        $atributos["ciudad"]=$ciudad;
        $atributos["fecha"]=date("Y-m-d H:i:s"); 
        $atributos["url"]=$url_peticion; 

        insertar("direcciones", $atributos);
    }


    if(isset($_GET["destino"]) && !empty($_GET["destino"]) &&isset($_GET["origen"]) && !empty($_GET["origen"])){
        $destino=formatEspaces($_GET["destino"]);
        $origen=formatEspaces($_GET["origen"]);

        $url_destino="https://maps.googleapis.com/maps/api/geocode/json?address=".$destino.",+Spain&language=es&country=spain&key=".$token;
        $url_origen="https://maps.googleapis.com/maps/api/geocode/json?address=".$origen.",+Spain&language=es&country=spain&key=".$token;

        $respuestaDestino = peticionCURL($url_destino);
        $respuestaOrigen = peticionCURL($url_origen);

        // Si la respuesta es correcta
        if($respuestaOrigen["status"] == "OK" && $respuestaDestino["status"] == "OK"){

            // calculo las coordenadas de las direcciones dadas
            $datosDestino['latitud'] = $respuestaDestino["results"]["0"]["geometry"]["location"]["lat"];
            $datosDestino['longitud']  =  $respuestaDestino["results"]["0"]["geometry"]["location"]["lng"];
            $datosOrigen['latitud'] = $respuestaOrigen["results"]["0"]["geometry"]["location"]["lat"];
            $datosOrigen['longitud']  =  $respuestaOrigen["results"]["0"]["geometry"]["location"]["lng"];
            // creo las url para hacer la petición a la api
            $url_distancias_bici = "https://maps.googleapis.com/maps/api/distancematrix/json?key=".$token."&origins=".$datosOrigen['latitud']."%2C".$datosOrigen['longitud']."&destinations=".$datosDestino['latitud']."%2C".$datosDestino['longitud']."&mode=bicycling";
            $url_distancias_coche = "https://maps.googleapis.com/maps/api/distancematrix/json?key=".$token."&origins=".$datosOrigen['latitud']."%2C".$datosOrigen['longitud']."&destinations=".$datosDestino['latitud']."%2C".$datosDestino['longitud'];

            $respuestaBici = peticionCURL($url_distancias_bici);
            $respuestaCoche = peticionCURL($url_distancias_coche);

            $infoRutaBici ="Distancia: ".$respuestaBici["rows"]["0"]["elements"]["0"]["distance"]["text"]." Tiempo: ".$respuestaBici["rows"]["0"]["elements"]["0"]["duration"]["text"];
            $infoRutaCoche ="Distancia: ".$respuestaCoche["rows"]["0"]["elements"]["0"]["distance"]["text"]." Tiempo: ".$respuestaCoche["rows"]["0"]["elements"]["0"]["duration"]["text"];

            // rutas para mostrar en el iframe
            $url_ruta_coche = "https://www.google.com/maps/embed/v1/directions?key=".$token."&origin=".$origen."&destination=".$destino."&mode=bicycling";
            $url_ruta_bici = "https://www.google.com/maps/embed/v1/directions?key=".$token."&origin=".$origen."&destination=".$destino."&mode=driving";

            $mensajeRuta = "<strong>Se ha indicado:</strong> <hr> Origen: ".$_GET["origen"]."<br>"."Destino: ".$_GET["destino"];
            $atributos["url"]=$url_ruta_coche." ".$url_ruta_bici; 
            $atributos["ruta_bici"] = $infoRutaCoche;
            $atributos["ruta_coche"] = $infoRutaCoche;
        }
        else{
            $mensajeRuta = "<strong>Se ha indicado:</strong> <hr> Direccion: ".$_GET["origen"]."<br>"."Destino: ".$_GET["destino"]." <hr> Dirección no válida";
            $atributos["url"]="FALLO"; 
        }

        $atributos["destino"]=$_GET["destino"];
        $atributos["origen"]=$_GET["origen"];
        $atributos["fecha"]=date("Y-m-d H:i:s"); 

        insertar("rutas", $atributos);
    }  

    if(isset($_GET['logs'])){
        $logsDirecciones = seleccionarTodos("direcciones");
        $logsRutas = seleccionarTodos("rutas");
        $verLogs = true;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/mdb.dark.min.css">
    <link rel="stylesheet" href="css/mdb.min.css">
    <link rel="stylesheet" href="css/mdb.rtl.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <script src="js/mdb.min.js"></script>

    <style>
        .material-icons {
            font-family: 'Material Icons';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;  /* Preferred icon size */
            display: inline-block;
            line-height: 1;
            text-transform: none;
            letter-spacing: normal;
            word-wrap: normal;
            white-space: nowrap;
            direction: ltr;

            /* Support for all WebKit browsers. */
            -webkit-font-smoothing: antialiased;
            /* Support for Safari and Chrome. */
            text-rendering: optimizeLegibility;

            /* Support for Firefox. */
            -moz-osx-font-smoothing: grayscale;

            /* Support for IE. */
            font-feature-settings: 'liga';
        }
    </style>

    <title>Geolocate API</title>
</head>
    
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light nav-tabs border-bottom border-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand text-primary " href="index.php" style="font-family: 'Syne', sans-serif;"><strong>GeolocateAPI</strong></a>   
            <div>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="#direccion">Ver dirección en el mapa</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#distancia">Calculo de distancia</a>
                </li>

                <li class="nav-item">
                    <a role="button" class="nav-link" href="?logs">Ver logs</a>
                </li>

            </ul>
            </div>
        </div>
    </nav>

    <div class = "container-fluid mt-4">
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <div class="col" id="direccion">
                <div class="card border border-primary mb-3">
                    <div class="card-header bg-primary text-light">Ver dirección en el mapa</div>
                    <div class="card-body text-dark">
                        <form method="get">
                            <label class="form-label" for="direccion">Indica una direccion:</label>
                            <input class="form-control" type="text" name="direccion" id="direccion" value="Avenida de Requejo, 33">

                            <label class="form-label" for="ciudad">Indica la ciudad:</label>
                            <input class="form-control" type="text" name="ciudad" id="ciudad" value="Zamora">


                            <button type="submit" class="btn btn-primary btn-block mt-3 mb-3">Ver en el mapa</button>
                        </form>

                        <?php
                        if(isset($mensajeDireccion)){
                            echo '<div class="alert alert-primary mt-3 mb-3" role="alert">';
                                echo $mensajeDireccion;
                            echo '</div>';
                        }
                        if(isset($imgSrc)){
                            echo "<img width='100%' height='450' class='img-fluid' src='".$imgSrc."' >"; 
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="col" id="distancia">
                <div class="card border border-primary mb-3">
                    <div class="card-header bg-primary text-light">Calcular distancia de la ruta</div>
                    <div class="card-body text-dark">
                        <form method="get">
                            <label class="form-label" for="origen">Indica una direccion Origen:</label>
                            <input class="form-control" type="text" name="origen" id="origen" value="Avenida de Requejo, 33, Zamora">

                            <label class="form-label" for="destino">Indica una direccion Destino:</label>
                            <input class="form-control" type="text" name="destino" id="destino" value="Pl. Caídos, s/n, Salamanca">


                            <button type="submit" class="btn btn-primary btn-block mt-3 mb-3">Calcular distancia</button>
                        </form>

                        <?php
                        if(isset($mensajeRuta)){
                            echo '<div class="alert alert-primary mt-3 mb-3" role="alert">';
                                echo $mensajeRuta;
                            echo '</div>';

                        }
                        if(isset($url_ruta_coche)){
                            echo '<div class="alert alert-success mt-3 mb-3" role="alert">';
                                echo "<strong><span class='material-icons'>directions_bike</span> Ruta más rápida en bicicleta</strong>";
                                echo "<br>";
                                echo $infoRutaBici;
                                echo "<br>";
                                echo "<strong><span class='material-icons'>directions_car_filled</span> Ruta más rápida en coche</strong>";
                                echo "<br>";
                                echo $infoRutaCoche;
                            echo '</div>';

                            echo "<iframe width='50%' height='450' frameborder='0' style='border:0' src='$url_ruta_coche' allowfullscreen></iframe>";
                            echo "<iframe width='50%' height='450' frameborder='0' style='border:0' src='$url_ruta_bici' allowfullscreen></iframe>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        
        <?php if($verLogs == true) { ?>
            <h3>Logs</h3>
            <hr>
            <div class="row row-cols-1 row-cols-md-1 row-cols-lg-2 g-4">
                
                <div class="col" id="direccion">
                    <div class="card border border-primary mb-3">
                        <div class="card-header bg-primary text-light">Busquedas de localizaciones</div>
                        <div class="card-body text-dark">
                            
                        <table class="table table-bordered border-dark table-light">
                            <thead>
                                <tr class="table-dark" >
                                <th scope="col">#</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Direccion</th>
                                <th scope="col">Cuidad</th>
                                <th scope="col">URL</th>
                                </tr>
                            </thead>
                            <tbody class="table-hover">
                                <?php for($i=0; $i< $logsDirecciones['n-indices']; $i++) { ?>
                                    <tr>
                                        <th scope="row"><?php echo $logsDirecciones[$i]["id"] ?></th>
                                        <td><?php echo $logsDirecciones[$i]["fecha"] ?></td>
                                        <td><?php echo $logsDirecciones[$i]["direccion"] ?></td>
                                        <td><?php echo $logsDirecciones[$i]["ciudad"] ?></td>
                                        <td>
                                                <div class="dropdown">
                                                    <button
                                                        class="btn btn-primary dropdown-toggle"
                                                        type="button"
                                                        id="dropdownMenuButton"
                                                        data-mdb-toggle="dropdown"
                                                        aria-expanded="false"
                                                    >
                                                        Ver
                                                    </button>
                                                    <div class="dropdown-menu p-4 text-lg-end" aria-labelledby="dropdownMenuButton">
                                                        <p><?php echo $logsDirecciones[$i]["url"]?></p>
                                                    </div>
                                                </div>
                                            </td>
                                    </tr>
                                <?php } ?> 
                            </tbody>
                        </table>        
                            
                        </div>
                    </div>
                </div> 

                <div class="col" id="direccion">
                    <div class="card border border-primary mb-3">
                        <div class="card-header bg-primary text-light">Calculos de rutas</div>
                        <div class="card-body text-dark">
                            
                            <table class="table table-bordered border-dark table-light">
                                <thead>
                                    <tr class="table-dark" >
                                    <th scope="col">#</th>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Origen</th>
                                    <th scope="col">Destino</th>
                                    <th scope="col">URL</th>
                                    <th scope="col">Ruta bici</th>
                                    <th scope="col">Ruta coche</th>
                                    </tr>
                                </thead>
                                <tbody class="table-hover">
                                    <?php for($i=0; $i< $logsRutas['n-indices']; $i++) { ?>
                                        <tr>
                                            <th scope="row"><?php echo $logsRutas[$i]["id"] ?></th>
                                            <td><?php echo $logsRutas[$i]["fecha"] ?></td>
                                            <td><?php echo $logsRutas[$i]["origen"] ?></td>
                                            <td><?php echo $logsRutas[$i]["destino"] ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button
                                                        class="btn btn-primary dropdown-toggle"
                                                        type="button"
                                                        id="dropdownMenuButton"
                                                        data-mdb-toggle="dropdown"
                                                        aria-expanded="false"
                                                    >
                                                        Ver
                                                    </button>
                                                    <div class="dropdown-menu p-4 text-lg-end" aria-labelledby="dropdownMenuButton">
                                                        <p><?php echo $logsRutas[$i]["url"]?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $logsRutas[$i]["ruta_bici"] ?></td>
                                            <td><?php echo $logsRutas[$i]["ruta_coche"] ?></td>
                                        </tr>
                                    <?php } ?> 
                                </tbody>
                            </table>                                    
                            
                        </div>
                    </div>
                </div> 

            </div>  
        <?php } ?>    

    </div>
    
    <footer class="text-white text-center text-lg-end" style="background-color: #0a4275;">
        <!-- Grid container -->
        <div class="container p-4">
            <!--Grid row-->
            <div class="row">
            <!--Grid column-->
            <div class="col-lg-4 col-md-12 mb-4 mb-md-0">
                <h5 class="text-uppercase">Mostrar una direccion en un mapa</h5>
                <p>
                    Dada un dirección postal, deberán obtener los datos de localización y posteriormente visualizarlos en un mapa.
                </p>
            </div>
            <!--Grid column-->

            <!--Grid column-->
            <div class="col-lg-4 col-md-12 mb-4 mb-md-0">
                <h5 class="text-uppercase">Calculo de distancias</h5>
                <p>
                    Dados dos lugares, se calcularán las distancias y el tiempo que se tarda en ir de un lugar a otro en diferentes medios de transporte.                 
                </p>
            </div>

            <!--Grid column-->
            <div class="col-lg-4 col-md-12 mb-4 mb-md-0">
                <h5 class="text-uppercase">Uso de base de datos</h5>
                <p>
                    Las consultas realizadas se deberán almacenar en un log o base de datos local para su posterior consulta.                  
                </p>
            </div>
            <!--Grid column-->
            </div>
            <!--Grid row-->
        </div>
        <!-- Grid container -->

        <!-- Copyright -->
        <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2);">
        Arquitecturas Orientadas a Servicios (SOA) 2021-2022, <a class="text-light" href="http://www.myki.studio/">Miguel Pérez León</a>
        </div>
        <!-- Copyright -->
    </footer>
    

</body>
</html>
