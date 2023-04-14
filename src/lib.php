<?php
    function base64_encrypt($plain_text, $password, $iv_len = 16) {
        $plain_text .= "\x13";
        $n = strlen($plain_text);
        if ($n % 16) 
            $plain_text .= str_repeat("\0", 16 - ($n % 16));
        
        $i = 0;
        $enc_text = get_rnd_iv($iv_len);
        $iv = substr($password ^ $enc_text, 0, 512);
        while ($i < $n) {
            $block = substr($plain_text, $i, 16) ^ pack('H*', md5($iv));
            $enc_text .= $block;
            $iv = substr($block . $iv, 0, 512) ^ $password;
            $i += 16;
        }
        $hasil=base64_encode($enc_text);
        return str_replace('+', '@', $hasil);
    }
    
    function base64_decrypt($enc_text, $password, $iv_len = 16) {
        $enc_text = str_replace('@', '+', $enc_text);
        $enc_text = base64_decode($enc_text);
        $n = strlen($enc_text);
        $i = $iv_len;
        $plain_text = '';
        $iv = substr($password ^ substr($enc_text, 0, $iv_len), 0, 512);
        while ($i < $n) {
            $block = substr($enc_text, $i, 16);
            $plain_text .= $block ^ pack('H*', md5($iv));
            $iv = substr($block . $iv, 0, 512) ^ $password;
            $i += 16;
        }
        return preg_replace('/\\x13\\x00*$/', '', $plain_text);
    }
    
    function get_rnd_iv($iv_len) {
        $iv = '';
        while ($iv_len-- > 0) {
            $iv .= chr(mt_rand() & 0xff);
        }
        return $iv;
    }

    $key = "63D3_6aNt3n9_B4nG3t";

    class DATABASE
    {
        protected $dbConn, $status, $principal_id, $principalgroup_id, $businessclass_id, $productcategory_id, $search, $pages, $nextperpage;
        public function __construct(){
            $this->getConnected();
            $this->status = 1;
            $this->principal_id = null;     
            $this->principalgroup_id = null;
            $this->businessclass_id = null;
            $this->productcategory_id = null;
            $this->search = '';
            $this->pages = 0;
            $this->nextperpage = 6;
        }

        public function getConnected(){
            if(isset($this->dbConn) && gettype($this->dbConn) === 'resource'){
                return $this->dbConn;
            }else{
                $serverName = "10.150.1.6";
                $connectionDBSSO = array("Database"=>"DBSSO", "UID"=>"sa", "PWD"=>"Sys@dmin00");
                $dbConn = sqlsrv_connect($serverName, $connectionDBSSO);
                $this->dbConn = $dbConn;
                if( $this->dbConn === false ){
                    die( print_r( sqlsrv_errors(), true ));
                }
                return $this->dbConn;
            }       
        }

        public function queryDB($queryString){
            $result = sqlsrv_query($this->getConnected(), $queryString);
            if($result === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            return $result;
        }

        public function close(){          
            if(isset($this->dbConn) && gettype($this->dbConn) === 'resource'){                              
                $status = sqlsrv_close($this->connection);                     
            }    
        }

        public function getBranchoffice($request){
            $str = "SELECT bo.branchoffice_code AS code,
                    bo.branchoffice_name AS name, 
                    bo.short_name,
                    bo.address,
                    bo.phone,
                    bo.province,                                                    
                    bo.url_maps,
                    bo.barcode_maps                                                   
                    FROM dbsso.dbo.branchoffice AS bo WITH (NOLOCK) 
                    WHERE bo.id NOT IN(1,17,18)
                    AND bo.status = $this->status
                    ORDER BY bo.id ASC";
            return $this->queryDB($str);
        }

        public function getProductCategory($request){
            // $str = "SELECT pc.id,
            //         pc.productcategory_name AS name
            //         FROM dbsso.dbo.v_webdma_product_detail AS v_webdma_product_detail WITH (NOLOCK) 
            //         LEFT JOIN dbsso.dbo.productcategory AS pc WITH (NOLOCK)
            //         ON pc.id = v_webdma_product_detail.productcategory_id
            //         WHERE pc.status = $this->status
            //         GROUP BY v_webdma_product_detail.productcategory_id, pc.id, pc.productcategory_name
            //         ORDER BY pc.productcategory_name ASC";

            $str = "SELECT pc.id,
                    pc.productcategory_name AS name
                    FROM dbsso.dbo.productcategory AS pc WITH (NOLOCK)
                    WHERE pc.status = $this->status
                    ORDER BY pc.productcategory_name ASC";
            return $this->queryDB($str);
        }

        public function getProductCategoryName($request){
            if(isset($request['id'])){
                $this->productcategory_id = $request['id'];
            }    
            $str = "SELECT pc.id,
                    pc.productcategory_name AS name
                    FROM dbsso.dbo.productcategory AS pc WITH (NOLOCK)                    
                    WHERE pc.status = $this->status
                    AND pc.id = $this->productcategory_id";                    
            return $this->queryDB($str);
        }

        public function getPrincipalInternalExternal($request){
            if(isset($request['principalgroup_id'])){
                $this->principalgroup_id = $request['principalgroup_id'];
            }    
            $str = "SELECT
                    principal.id,
                    principal.principal_code,
                    principal.principal_name
                    FROM dbsso.dbo.principal AS principal WITH (NOLOCK)
                    WHERE principal.principalgroup_id IN ($this->principalgroup_id)
                    AND principal.status = $this->status
                    GROUP BY principal.id, principal.principal_code, principal.principal_name
                    ORDER BY principal.principal_name ASC";
            return $this->queryDB($str);
        }        

        public function getProductList($request){
            if(isset($request['search'])){
                $this->search = $request['search'];                                                
            }
            if(isset($request['principal_id'])){
                $this->principal_id = $request['principal_id'];
            }                
            if(isset($request['productcategory_id'])){
                $this->productcategory_id = $request['productcategory_id'];
            }
            if(isset($request['page'])){
                $this->pages = $request['page'];
            }
            $str = "SELECT
                    v_webdma_product_detail.product_id,
                    v_webdma_product_detail.product_code,
                    v_webdma_product_detail.product_name,
                    v_webdma_product_detail.principal_code,
                    v_webdma_product_detail.principal_name,
                    v_webdma_product_detail.businessclass_code,
                    v_webdma_product_detail.businessclass_name,
                    v_webdma_product_detail.productcategory_name,
                    v_webdma_product_detail.image
                    FROM dbsso.dbo.v_webdma_product_detail AS v_webdma_product_detail WITH (NOLOCK)
                    WHERE v_webdma_product_detail.featured = $this->status
                    GROUP BY v_webdma_product_detail.product_id,
                            v_webdma_product_detail.product_code,
                            v_webdma_product_detail.product_name,
                            v_webdma_product_detail.principal_code,
                            v_webdma_product_detail.principal_name,
                            v_webdma_product_detail.businessclass_code,
                            v_webdma_product_detail.businessclass_name,
                            v_webdma_product_detail.productcategory_name,
                            v_webdma_product_detail.image,
                            v_webdma_product_detail.featured,
                            v_webdma_product_detail.businessclass_id
                    ORDER BY v_webdma_product_detail.product_name ASC
                    OFFSET $this->pages ROWS FETCH NEXT $this->nextperpage ROWS ONLY";           

            if($this->search !== null && $this->search !== ''){
                $str = "SELECT
                            v_webdma_product_detail.product_id,
                            v_webdma_product_detail.product_code,
                            v_webdma_product_detail.product_name,
                            v_webdma_product_detail.principal_code,
                            v_webdma_product_detail.principal_name,
                            v_webdma_product_detail.businessclass_code,
                            v_webdma_product_detail.businessclass_name,
                            v_webdma_product_detail.productcategory_name,
                            v_webdma_product_detail.image
                            FROM dbsso.dbo.v_webdma_product_detail AS v_webdma_product_detail WITH (NOLOCK)
                            WHERE v_webdma_product_detail.featured = $this->status
                            AND ( 
                                v_webdma_product_detail.product_name LIKE '%$this->search%' 
                                OR v_webdma_product_detail.product_code LIKE '%$this->search%'                         
                                OR v_webdma_product_detail.principal_name LIKE '%$this->search%'                                                     
                                OR v_webdma_product_detail.principal_code LIKE '%$this->search%'   
                                OR v_webdma_product_detail.productcategory_name LIKE '%$this->search%'
                            )
                            GROUP BY v_webdma_product_detail.product_id,
                                    v_webdma_product_detail.product_code,
                                    v_webdma_product_detail.product_name,
                                    v_webdma_product_detail.principal_code,
                                    v_webdma_product_detail.principal_name,
                                    v_webdma_product_detail.businessclass_code,
                                    v_webdma_product_detail.businessclass_name,
                                    v_webdma_product_detail.productcategory_name,
                                    v_webdma_product_detail.image,
                                    v_webdma_product_detail.featured,
                                    v_webdma_product_detail.businessclass_id
                            ORDER BY v_webdma_product_detail.product_name ASC
                            OFFSET $this->pages ROWS FETCH NEXT $this->nextperpage ROWS ONLY"; 
            }
            if($this->principal_id !== null && $this->principal_id !== ''){
                $str = "SELECT
                        v_webdma_product_detail.product_id,
                        v_webdma_product_detail.product_code,
                        v_webdma_product_detail.product_name,
                        v_webdma_product_detail.principal_code,
                        v_webdma_product_detail.principal_name,
                        v_webdma_product_detail.businessclass_code,
                        v_webdma_product_detail.businessclass_name,
                        v_webdma_product_detail.productcategory_name,
                        v_webdma_product_detail.image
                        FROM dbsso.dbo.v_webdma_product_detail AS v_webdma_product_detail WITH (NOLOCK)
                        WHERE v_webdma_product_detail.featured = $this->status
                        AND v_webdma_product_detail.principal_id = $this->principal_id
                        GROUP BY v_webdma_product_detail.product_id,
                                v_webdma_product_detail.product_code,
                                v_webdma_product_detail.product_name,
                                v_webdma_product_detail.principal_code,
                                v_webdma_product_detail.principal_name,
                                v_webdma_product_detail.businessclass_code,
                                v_webdma_product_detail.businessclass_name,
                                v_webdma_product_detail.productcategory_name,
                                v_webdma_product_detail.image,
                                v_webdma_product_detail.featured,
                                v_webdma_product_detail.businessclass_id
                        ORDER BY v_webdma_product_detail.product_name ASC
                        OFFSET $this->pages ROWS FETCH NEXT $this->nextperpage ROWS ONLY";
            }
            if($this->productcategory_id !== null && $this->productcategory_id !== ''){
                $str = "SELECT
                        v_webdma_product_detail.product_id,
                        v_webdma_product_detail.product_code,
                        v_webdma_product_detail.product_name,
                        v_webdma_product_detail.principal_code,
                        v_webdma_product_detail.principal_name,
                        v_webdma_product_detail.businessclass_code,
                        v_webdma_product_detail.businessclass_name,
                        v_webdma_product_detail.productcategory_name,
                        v_webdma_product_detail.image
                        FROM dbsso.dbo.v_webdma_product_detail AS v_webdma_product_detail WITH (NOLOCK)
                        WHERE v_webdma_product_detail.featured = $this->status
                        AND v_webdma_product_detail.productcategory_id = $this->productcategory_id
                        GROUP BY v_webdma_product_detail.product_id,
                                v_webdma_product_detail.product_code,
                                v_webdma_product_detail.product_name,
                                v_webdma_product_detail.principal_code,
                                v_webdma_product_detail.principal_name,
                                v_webdma_product_detail.businessclass_code,
                                v_webdma_product_detail.businessclass_name,
                                v_webdma_product_detail.productcategory_name,
                                v_webdma_product_detail.image,
                                v_webdma_product_detail.featured,
                                v_webdma_product_detail.businessclass_id
                        ORDER BY v_webdma_product_detail.product_name ASC
                        OFFSET $this->pages ROWS FETCH NEXT $this->nextperpage ROWS ONLY";                                
            }                          
            return $this->queryDB($str);
        }

        public function getPageProduct($request){
            if(isset($request['search'])){
                $this->search = $request['search'];
            }    
            if(isset($request['principal_id'])){
                $this->principal_id = $request['principal_id'];
            }
            if(isset($request['productcategory_id'])){
                $this->productcategory_id = $request['productcategory_id'];
            }

            $str = "SELECT COUNT(v_webdma_product_detail.id) as count_page
                    FROM v_webdma_product_detail WITH(NOLOCK)
                    WHERE v_webdma_product_detail.featured = 1";

            if($this->search !== null && $this->search !== ''){
                $str = "SELECT COUNT(v_webdma_product_detail.id) as count_page
                        FROM v_webdma_product_detail WITH(NOLOCK)
                        WHERE v_webdma_product_detail.featured = 1
                        AND (
                                v_webdma_product_detail.product_name LIKE '%$this->search%'
                                OR v_webdma_product_detail.product_code LIKE '%$this->search%'                       
                                OR v_webdma_product_detail.principal_name LIKE '%$this->search%'                                                     
                                OR v_webdma_product_detail.principal_code LIKE '%$this->search%'
                                OR v_webdma_product_detail.productcategory_name LIKE '%$this->search%'
                            )";
            }
            if($this->principal_id !== null && $this->principal_id !== ''){
                $str = "SELECT COUNT(v_webdma_product_detail.id) as count_page
                        FROM v_webdma_product_detail WITH(NOLOCK)
                        WHERE v_webdma_product_detail.featured = 1
                        AND v_webdma_product_detail.principal_id = $this->principal_id";
            }
            if($this->productcategory_id !== null && $this->productcategory_id !== ''){
                $str = "SELECT COUNT(v_webdma_product_detail.id) as count_page
                        FROM v_webdma_product_detail WITH(NOLOCK)
                        WHERE v_webdma_product_detail.featured = 1
                        AND v_webdma_product_detail.productcategory_id = $this->productcategory_id";
            }
            return $this->queryDB($str);
        }

        public function alertmessage($message){
            echo "<script language='javascript'>alert('$message');</script>";
        }
    }           

?>