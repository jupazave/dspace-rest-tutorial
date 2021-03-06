<?php

    function config($propertie){

        switch ($propertie){
            case 'dspace.ip':
                return "localhost";
                break;
            case 'dspace.port':
                return "8080";
                break;
            case 'dspace.login':
                return "email=ejemplo@ejemplo.com&password=pass";
                break;
            default:
                break;
        }
        return "";
    }

    // Obtiene la variable JSESSIONID, es importante iniciar sesión con loginToDspace seguido de esto.
    function getUserSessionID(){

        // Get the session status
        $ch = curl_init("http://".config('dspace.ip').":".config('dspace.port')."/rest/status");
        curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
        //curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt'); // quitar esto y aparece el jsession
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $userResults = curl_exec ($ch);
        echo "<p>".$userResults."</p>";

        // Get the JSESSIONID cookie
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $userResults, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        var_dump($cookies);
        $jsessionID = $cookies['JSESSIONID'];
        echo $jsessionID;

        curl_close ($ch);
        return $jsessionID;
    }

    // Inicia sesión en Dspace
    function loginToDspace(){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://".config('dspace.ip').":".config('dspace.port')."/rest/login");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, config('dspace.login'));
        curl_setopt($ch, CURLOPT_POST, 1);
        $headers = array();
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $agent = "curl/7.53.1";
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != '200'){

            echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
            exit();
        }

        // get response header
        $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT );
        echo "<p>".$headerSent."</p>";

        curl_close ($ch);
    }

    function getItems($jsessionID){

        $url = "http://".config('dspace.ip').":".config('dspace.port')."/rest/items/?limit=200";

        $ch = curl_init($url);

        $cookieses = "JSESSIONID=".$jsessionID;

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $cookieses);

        $result = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != '200'){
            //throw $this->getException($ch);
            echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
            exit();
        }

        curl_close($ch);
        return $result;
    }

    function getItemMetadata($itemId, $jsessionID){

        $url = "http://".config('dspace.ip').":".config('dspace.port')."/rest/items/$itemId/metadata";

        $ch = curl_init($url);

        $cookieses = "JSESSIONID=".$jsessionID;

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $cookieses);

        $result = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != '200'){
            //throw $this->getException($ch);
            echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
            exit();
        }

        curl_close($ch);
        return $result;
    }

	/**
     * Send an Item to the dspace server.
     * @param $dspaceItem Json format for the item, (dspace)
     * @param $collectionDspaceId
     * @return mixed|string
     * @throws \Exception
     */
    function postItem($dspaceItem, $collectionDspaceId, $jsessionID){

        /**
         * $url dirección del repositorio dspace (en este caso para consumir un servicio rest
         */
        $url = "http://".config('dspace.ip').':'.config('dspace.port')."/rest/collections/$collectionDspaceId/items";

        $ch = curl_init($url); //iniciar el canal de comunicación a una url definida.

        /**
         * Cabeceras necesarias para realizar la petición POST a dspace
         * * Content-type: json, para que los datos a enviar tengan una estructura json y sea reconocible por dspace (también puede ser xml)
         * * Accept: json, para que la respuesta legue en formato json.
         * * rest-dspace-token: xxxxxxxxxx , el token recuperado del servicio "login" al dspace debe enviarse en la cabecera.
         */

        $header = array(
            "Content-type: application/json",
            "Accept: application/json"
            //"rest-dspace-token: ".config('dspace.token')
        );

        $cookieses = "JSESSIONID=".$jsessionID;

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//para recibir la respuesta del httprequest.
        curl_setopt($ch, CURLOPT_POST, true); //avisa que el método de la petición sera POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dspaceItem); // Metadatos del item que será enviado a dspace en formato Json, con la estructura que dspace espera.
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //prepara las cabeceras para la petición  httprequest.
        curl_setopt($ch, CURLOPT_COOKIE, $cookieses);

        $dspaceItem = curl_exec($ch); //ejecuta la petición httprequest

        /**
         * curl_getinfo() recupera el status code de la respuesta, los cuales tienen diferentes significados,
         * donde un 200 significa que la petición se ha realizado con éxito.
         * Los demás posibles respuestas pueden encontrarse en la sección HTTP Response en la siguiente documentación:
         * https://github.com/DSpace/DSpace/tree/master/dspace-rest
         */
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != '200'){
            //throw $this->getException($ch);
            echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
            exit();
        }

        curl_close($ch); //Cierra el canal
        return $dspaceItem;
    }

    function postMetadataToItem($itemMetadata, $itemId, $jsessionID){

        $url = "http://".config('dspace.ip').':'.config('dspace.port')."/rest/items/$itemId/metadata";

        $ch = curl_init($url);

        $header = array(
            "Content-type: application/json",
            "Accept: application/json"
        );

        $cookieses = "JSESSIONID=".$jsessionID;

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $itemMetadata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_COOKIE, $cookieses);

        $dspaceItem = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != '200'){
            echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
            exit();
        }

        curl_close($ch);
        return $dspaceItem;
    }

    function postBitstreamToItem($itemId, $itemDescription, $filename, $filepath, $jsessionID){

        $name = urlencode($filename);
        $description = urlencode($itemDescription);

        $url = "http://".config('dspace.ip').":".config('dspace.port')."/rest/items/$itemId/bitstreams?name=$name&description=$description";

        $ch = curl_init($url);

        $cookieses = "JSESSIONID=".$jsessionID;
        $headers = array("Content-Type: text/plain", "Accept: application/json");

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filepath));
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents( realpath($filepath) ));

        curl_setopt($ch, CURLOPT_COOKIE, $cookieses);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != '200'){

            echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
            exit();
        }

        $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT );
        echo "<p>".$headerSent."</p>";

        curl_close($ch);
        return $result;
    }

    function postBitstreamHTML($itemId, $itemDescription, $jsessionID){

        $filename = $_FILES['fileToUpload']['name'];
        $filedata = $_FILES['fileToUpload']['tmp_name'];

        var_dump($_FILES);

        $description = urlencode($itemDescription);

        $url = "http://".config('dspace.ip').':'.config('dspace.port')."/rest/items/$itemId/bitstreams?name=$filename&description=$description";

        $ch = curl_init($url);

        $cookieses = "JSESSIONID=".$jsessionID;
        $headers = array("Content-Type: text/plain", "Accept: application/json");

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filedata));

        curl_setopt($ch, CURLOPT_COOKIE, $cookieses);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != '200'){

            echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
            exit();
        }

        $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT );
        echo "<p>".$headerSent."</p>";

        curl_close($ch);
        return $result;
    }

    function putMetadataInItem($itemMetadata, $itemId, $jsessionID){

        $url = "http://".config('dspace.ip').":".config('dspace.port')."/rest/items/$itemId/metadata";

        $ch = curl_init($url);

        $header = array(
            "Content-type: application/json",
            "Accept: application/json"
        );

        $cookieses = "JSESSIONID=".$jsessionID;

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $itemMetadata);
        curl_setopt($ch, CURLOPT_COOKIE, $cookieses);

        $dspaceItem = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != '200'){
            echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
            exit();
        }

        curl_close($ch);
        return $dspaceItem;
    }

    function deleteItem( $itemID, $jsessionID ){

        $url = "http://".config('dspace.ip').":".config('dspace.port')."/rest/items/$itemID";

        $ch = curl_init($url);

        $cookieses = "JSESSIONID=".$jsessionID;

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $cookieses);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

        $result = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != '200'){
            //throw $this->getException($ch);
            echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
            exit();
        }

        curl_close($ch);
    }
?>
