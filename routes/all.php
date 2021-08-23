<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/db.php';
require 'helpers.php';

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->setBasePath("/newproject/public/index.php");


/* Authentication */
$app->post('/tutorAuth', function (Request $request, Response $response, array $args) {
    
    $parsedBody = $request->getParsedBody();
    
    $id = $parsedBody['tusername'];
    $pass = $parsedBody['password'];
    

    $sql = "SELECT * FROM tutor where tusername = '$id'";

    try {
        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->query($sql);
        $friends = $stmt->fetchAll(PDO::FETCH_OBJ);
        $rows = count($friends);
        
        /*Mock Authentication tutor is not in database insert the tutor*/
        if($rows==0)
        {
            $sql = "INSERT INTO tutor (tusername,password) VALUE(:tusername,:password)";
        
        

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':tusername',$id);
            $stmt->bindParam(':password',$pass);
        

            $result = $stmt->execute();
        }
        $db = null;
        
        //require 'JwtHandler.php';
        $jwt = new JwtHandler();
        
        $token = $jwt->_jwt_encode_data(
            'http://localhost/VC/newproject/public',
            array("username"=>$id,"role"=>"tutor")
        );
        
        $response->getBody()->write(json_encode($token));

        return $response;
    }
    catch(PDOException $e)
    {
        $error = array(
            "message"=>$e->getMessage()
        );
        $response->getBody()->write(json_encode($error));

        return $response;
    }
    return $response;
});


$app->post('/studentAuth', function (Request $request, Response $response, array $args) {
    $parsedBody = $request->getParsedBody();
    
    $id = $parsedBody['susername'];
    $pass = $parsedBody['password'];
    

    $sql = "SELECT * FROM student where susername = '$id'";
    try {
        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->query($sql);
        $friends = $stmt->fetchAll(PDO::FETCH_OBJ);
        $rows = count($friends);
        
        /*Mock Authentication tutor is not in database insert the tutor*/
        if($rows==0)
        {
            $sql = "INSERT INTO student (susername,password) VALUE(:susername,:password)";
        
        

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':susername',$id);
        $stmt->bindParam(':password',$pass);
    

        $result = $stmt->execute();
        }
        $db = null;
        
        //require 'JwtHandler.php';
        $jwt = new JwtHandler();
        
        $token = $jwt->_jwt_encode_data(
            'http://localhost/VC/newproject/public',
            array("username"=>$id,"role"=>"student")
        );
        
        $response->getBody()->write(json_encode($token));

        return $response;
    }
    catch(PDOException $e)
    {
        $error = array(
            "message"=>$e->getMessage()
        );
        $response->getBody()->write(json_encode($error));

        return $response;
    }
    return $response;
});


/*Add new assignment using tutor id  in database*/


$app->post('/createAssignment', function (Request $request, Response $response, array $args) {
    
    /*User differentiation------------------------------------------- */
    $parsedBody = $request->getParsedBody();
    $token = $parsedBody['token'];
    $jwt = new JwtHandler();
    $data =  $jwt->_jwt_decode_data(trim($token));
    $x = (array) $data;
    /*------------------------------------------------------------*/ 
    if ($x["role"]=="tutor")
    {
    
        

    $sql = "INSERT INTO assignment (description,publishedat,deadline,tusername) VALUE(:description,:publishedat,:deadline,:tusername);";

    try{
        $tusername = $x["username"];

   $description = $parsedBody['description'];
   $publishedat = $parsedBody['publishedat'];
   $today = date('Y-m-d');
   $deadline =  $parsedBody['deadline'];
    $students = (array) $parsedBody['students'];

        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':tusername',$tusername);
        $stmt->bindParam(':description',$description);
        $stmt->bindParam(':publishedat',$publishedat);
        $stmt->bindParam(':deadline',$deadline);
        
        $result = $stmt->execute();
        $lastid = $conn->lastInsertId();
        $status='PENDING';

        /*if($publishedat>$today){
            $status='SCHEDULED';
        }*/
        foreach($students as $student)
        {
            foreach($student as $id)
            {
                //var_dump($id);
                $sqla = "INSERT INTO tasks (assignmentid,susername,status) VALUE(:assignmentid,:susername,:status)";
                $stmt = $conn->prepare($sqla);
                $stmt->bindParam(':susername',$id);
                $stmt->bindParam(':assignmentid',$lastid);
                $stmt->bindParam(':status',$status);

                $stmt->execute();

            }
        }
        

        $db = null;
        if ($result=="true") {
            $response->getBody()->write(json_encode($result.' - NEW ASSIGNMENT CREATED with id '.$lastid));
        }
        else{
            $response->getBody()->write(json_encode($result.' - Unable to create Assignment '));
        }
      
        return $response;
    }
    catch(PDOException $e)
    {
        $error = array(
            "message"=>$e->getMessage()
        );
        $response->getBody()->write(json_encode($error));

        return $response;
    }
    return $response;
    }
    else
    {
        $response->getBody()->write(json_encode("NOT AUTHENTICATED"));

        return $response;
    }
});

/*update Assignment*/
$app->post('/updateAssignment', function (Request $request, Response $response, array $args) {
   
       /*User differentiation------------------------------------------- */
       $parsedBody = $request->getParsedBody();
       $token = $parsedBody['token'];
       $jwt = new JwtHandler();
       $data =  $jwt->_jwt_decode_data(trim($token));
       $x = (array) $data;
       /*------------------------------------------------------------*/ 

    $tusername = $x["username"];
    $id = $parsedBody['assignmentid'];

   
   // $students = (array) $parsedBody['students'];
    if ($x["role"]=="tutor") {
        $sql = "UPDATE assignment set description=:description,publishedat=:publishedat,deadline=:deadline where assignmentid=:assignmentid and tusername=:tusername ;";

        try {
            $description = $parsedBody['description'];
            $publishedat = $parsedBody['publishedat'];
            $deadline =  $parsedBody['deadline'];

            $db = new DB();
            $conn = $db->connect();

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':tusername', $tusername);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':publishedat', $publishedat);
            $stmt->bindParam(':deadline', $deadline);
            $stmt->bindParam(':assignmentid', $id);
        
            $result = $stmt->execute();
           
        

            $db = null;
            if ($result=="true") {
                $response->getBody()->write(json_encode($result.' - Assignment Updated Successfully id - '.$id));
            }
            else
            {
                $response->getBody()->write(json_encode($result. ' Unable to updated Assignment'));
            }
            return $response;
        } catch (PDOException $e) {
            $error = array(
            "message"=>$e->getMessage()
        );
             $response->getBody()->write(json_encode($error));

            return $response;
        }
        return $response;
    }
    else
    {
        $response->getBody()->write(json_encode("NOT AUTHENTICATED"));

        return $response;
    }
});

/*delete Assignment*/
$app->post('/deleteAssignment', function (Request $request, Response $response, array $args) {
   
     /*User differentiation------------------------------------------- */
     $parsedBody = $request->getParsedBody();
     $token = $parsedBody['token'];
     $jwt = new JwtHandler();
     $data =  $jwt->_jwt_decode_data(trim($token));
     $x = (array) $data;
     /*------------------------------------------------------------*/ 
     if ($x["role"]=="tutor") {
        

         $sql = "DELETE FROM assignment where assignmentid=:assignmentid and tusername=:tusername ;";

         try {
            $tusername = $x["username"];
            $id = $parsedBody['assignmentid'];
   

             $db = new DB();
             $conn = $db->connect();

             $stmt = $conn->prepare($sql);
             $stmt->bindParam(':tusername', $tusername);
      
             $stmt->bindParam(':assignmentid', $id);
        
             $result = $stmt->execute();
        
        

             $db = null;
             
             $response->getBody()->write(json_encode($result));
             // var_dump($lastid);
             return $response;
         } catch (PDOException $e) {
             $error = array(
            "message"=>$e->getMessage()
        );
             $response->getBody()->write(json_encode($error));

             return $response;
         }
         return $response;
     }
     else
     {
        $response->getBody()->write(json_encode("NOT AUTHENTICATED"));

        return $response;
     }
});


$app->post('/getDetails', function (Request $request, Response $response, array $args) {
    
    /*User differentiation------------------------------------------- */
    $parsedBody = $request->getParsedBody();
    $token = $parsedBody['token'];
    $jwt = new JwtHandler();
    $data =  $jwt->_jwt_decode_data(trim($token));
    $x = (array) $data;
    /*------------------------------------------------------------*/ 
    if ($x["role"]=="tutor")
    {
    
    $sql = "SELECT susername,remarks FROM tasks where assignmentid=:assignmentid and status='SUBMITTED'";

    try{
        //$tusername = $x["username"];

   $assignmentid = $parsedBody['assignmentid'];
   

        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':assignmentid',$assignmentid);
      
      
        
        $result = $stmt->execute();        
        $result =  $stmt->fetchAll(PDO::FETCH_OBJ);

        $db = null;
        $response->getBody()->write(json_encode($result));
       // var_dump($lastid);
        return $response;
    }
    catch(PDOException $e)
    {
        $error = array(
            "message"=>$e->getMessage()
        );
        $response->getBody()->write(json_encode($error));

        return $response;
    }
    return $response;
    }

    /*----------------------------------------------- */

   else if ($x["role"]=="student")
    {
    
    $sql = "SELECT status,remarks  from tasks where assignmentid=:assignmentid and susername=:susername";

    try{
        $susername = $x["username"];

   $assignmentid = $parsedBody['assignmentid'];
   

        $db = new DB();
        $conn = $db->connect();

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':assignmentid',$assignmentid);
        $stmt->bindParam(':susername',$susername);
        $result = $stmt->execute();        
        $result =  $stmt->fetchAll(PDO::FETCH_OBJ);
        /*$today = date('Y-m-d');
        if($result>$today)
        {
            $result='SCHEDULED & PENDING';
        }
        else
        {
            $result='ONGOING';

        }
        $db = null; */
        $response->getBody()->write(json_encode($result));
       // var_dump($lastid);
        return $response;
    }
    catch(PDOException $e)
    {
        $error = array(
            "message"=>$e->getMessage()
        );
        $response->getBody()->write(json_encode($error));

        return $response;
    }
    return $response;
    }

    /********* ---------------------------------------- */
    else
    {
        $response->getBody()->write(json_encode("NOT AUTHENTICATED"));

        return $response;
    }
});

$app->post('/addSubmission', function (Request $request, Response $response, array $args) {
    
    /*User differentiation------------------------------------------- */
    $parsedBody = $request->getParsedBody();
    $token = $parsedBody['token'];
    $jwt = new JwtHandler();
    $data =  $jwt->_jwt_decode_data(trim($token));
    $x = (array) $data;
    /*------------------------------------------------------------*/ 
    if ($x["role"]=="student")
    {
    
    /* UPDATE tasks set remarks='jdkfhniu',status='SUBMITTED' where assignmentid=11 and susername='S1' and status!='SUBMITTED' */    

    $sql = "UPDATE tasks  set remarks=:remarks,status=:status where assignmentid=:assignmentid and susername=:susername and status='PENDING' ;";
    $sqla = "SELECT * from assignment where assignmentid=:assignmentid";
    try{
        $susername = $x["username"];

   $remarks = $parsedBody['remarks'];
   $assignmentid = $parsedBody['assignmentid'];
   $status = 'SUBMITTED';
   $today = date('Y-m-d');
  

        $db = new DB();
        $conn = $db->connect();
    
        //----------------------/
        $stmt = $conn->prepare($sqla);
        $stmt->bindParam(':assignmentid',$assignmentid);
        $result = $stmt->execute();
        $result =  $stmt->fetch(PDO::FETCH_ASSOC);
       // var_dump($result);
        if(isset($result["publishedat"]) && $result["publishedat"]>$today)
        {
            $response->getBody()->write(json_encode("SCHEDULED !!"));

            return $response;
        }
        else if(isset($result["deadline"]) && $result["deadline"]<$today)
        {
            $status='OVERDUE';
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':susername',$susername);
            $stmt->bindParam(':remarks',$remarks);
            $stmt->bindParam(':assignmentid',$assignmentid);
            $stmt->bindParam(':status',$status);
            
            $result = $stmt->execute();

            $response->getBody()->write(json_encode("OVERDUE"));

            return $response;
        }
        else
        {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':susername',$susername);
            $stmt->bindParam(':remarks',$remarks);
            $stmt->bindParam(':assignmentid',$assignmentid);
            $stmt->bindParam(':status',$status);
            
            $result = $stmt->execute();

        }
        //----------------------/

      


        $db = null;
        $response->getBody()->write(json_encode($result));
       // var_dump($lastid);
        return $response;
    }
    catch(PDOException $e)
    {
        $error = array(
            "message"=>$e->getMessage()
        );
        $response->getBody()->write(json_encode($error));

        return $response;
    }
    return $response;
    }
    else
    {
        $response->getBody()->write(json_encode("NOT AUTHENTICATED"));

        return $response;
    }
});

$app->post('/fetchDetails', function (Request $request, Response $response, array $args) {
    
    /*User differentiation------------------------------------------- */
    $parsedBody = $request->getParsedBody();
    $token = $parsedBody['token'];
    $filter = $parsedBody['filter'];
    $jwt = new JwtHandler();
    $data =  $jwt->_jwt_decode_data(trim($token));
    $x = (array) $data;
    $today = date('Y-m-d');
  
    /*------------------------------------------------------------*/ 
    if ($x["role"]=="tutor")
    {
    
    $sqls = "SELECT * FROM assignment where tusername=:tusername and publishedat<:today";
    $sqlo = "SELECT * FROM assignment where tusername=:tusername and deadline>=:today and publishedat<=:today";

    try{
        $tusername = $x["username"];

  // $assignmentid = $parsedBody['assignmentid'];
   

        $db = new DB();
        $conn = $db->connect();

        if ($filter=='ONGOING') {
            $stmt = $conn->prepare($sqlo);
        }
        else if($filter=='SCHEDULED')
        {
            $stmt = $conn->prepare($sqls);
        }
        else
        {
            $response->getBody()->write(json_encode("filter applicable ONGOING or SCHEDULED"));

        return $response;
        }
        $stmt->bindParam(':tusername',$tusername);
        $stmt->bindParam(':today',$today);
      
        
        $result = $stmt->execute();        
        $result =  $stmt->fetchAll(PDO::FETCH_OBJ);

        $db = null;
        $response->getBody()->write(json_encode($result));
       // var_dump($lastid);
        return $response;
    }
    catch(PDOException $e)
    {
        $error = array(
            "message"=>$e->getMessage()
        );
        $response->getBody()->write(json_encode($error));

        return $response;
    }
    return $response;
    }

    /*----------------------------------------------- */

   else if ($x["role"]=="student")
    {
    
        $sqla = "SELECT * FROM tasks where susername=:susername ";
        $sqlp = "SELECT * FROM tasks where susername=:susername and status='PENDING' ";
        $sqls = "SELECT * FROM tasks where susername=:susername and status='SUBMITTED'";
        $sqlo = "SELECT * FROM tasks where susername=:susername and status='OVERDUE'";

    try{
        $susername = $x["username"];

   $filter = $parsedBody['filter'];
   

        $db = new DB();
        $conn = $db->connect();

        if ($filter=="ALL") {
            $stmt = $conn->prepare($sqla);
        }
        else if($filter=="PENDING")
        {
            $stmt = $conn->prepare($sqlp);
        }
        else if($filter=="SUBMITTED")
        {
            $stmt = $conn->prepare($sqls);
        }
        else if($filter=="OVERDUE")
        {
            $stmt = $conn->prepare($sqlo);
        }
        else
        {
            $response->getBody()->write(json_encode("filter applicable ALL , PENDING , SUBMITTED, OVERDUE "));

            return $response;
        }
        $stmt->bindParam(':susername',$susername);

        $result = $stmt->execute();        
        $result =  $stmt->fetchAll(PDO::FETCH_OBJ);
      
        $db = null;
        $response->getBody()->write(json_encode($result));
       // var_dump($lastid);
        return $response;
    }
    catch(PDOException $e)
    {
        $error = array(
            "message"=>$e->getMessage()
        );
        $response->getBody()->write(json_encode($error));

        return $response;
    }
    return $response;
    }

    /********* ---------------------------------------- */
    else
    {
        $response->getBody()->write(json_encode("NOT AUTHENTICATED"));

        return $response;
    }
});
?>