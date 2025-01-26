<?php

namespace App\Http\Controllers;


use App\Models\Datacrm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class integrateController extends Controller
{

    private $keaser;
    public function __construct()
    {

        $conecK = config('array.conections.kaeser');
        $this->keaser = new Datacrm($conecK['user'],$conecK['token'],$conecK['url']);

    }



    /**
     * Find Conection CRM
     *
     * @param [type] $value
     * @return void
     */
    function findDistroConfig( $value ){

        $arraytest =  config('array.conections');
        $field     = 'id_kaeser';

        foreach($arraytest as $key => $product)
        {
            if(isset( $product[$field]))
                if ( $product[$field] === $value )
                    return $key;
        }
        return false;
    }

    


    /**
     * Undocumented function
     *
     * @return void
     */
    public function movetokeaser(Request $request){


        Log::channel('daily')->error('Request '.$request->id.' '.$request->crm);

        $crm            = $request->crm;
        $idDistro       = $request->id;
        $potential_no   = $request->potential_no;

        try{


            $returned = $this->createPotential($idDistro,$crm,$potential_no);

            $this->searchduplicated($returned['result'],$returned['nit'],$idDistro,$potential_no,$crm);

            return TRUE;

        }catch(\Exception $e){


             Log::channel('daily')->error('Error moving '.$request->id.' '.$request->crm.' '.$request->potential_no.' - '.$e->getMessage() );
             return TRUE;
             
        }
        

    }


    /**
     * Updates status in CRM Distro if changes.
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function updateStatus(Request $request){

        $nextstep           = $request->pot;    
        $opportunity_type   = $request->opportunity_type;
        $related_to         = $request->related_to;
        

        $crm = $this->findDistroConfig($related_to);
      
        if($crm != false){
            $conections = config('array.conections.'.$crm);
            $distro = new Datacrm($conections['user'],$conections['token'],$conections['url']);


            $potDist = $distro->searchbyPotentialNo($nextstep);


            if($potDist != null){
                $updateinfo = array(
                    'id'               => $potDist['id'],
                    'opportunity_type' => $opportunity_type,
                    'nextstep'         => ''
                );

                $upd = $distro->updatePotential($updateinfo);
            }
        }

        return true;

    }


    
    /**
     * Undocumented function
     *
     * @return void
     */
    public function updateKaeser(Request $request){


        $potno             = $request->potentialno; 
        $idDistroPot       = $request->id;
        $sale_stage        = $request->salestage;
        $crm               = $request->crm;


        $conections = config('array.conections.'.$crm);
        $distro = new Datacrm($conections['user'],$conections['token'],$conections['url']);
        
        $related_to        = $conections['id_kaeser'];


        $search = $this->keaser->searchPotential( ['nextstep' => $potno, 'related_to' => $related_to] );


        Log::channel('daily')->error('potno '.$request->potentialno.' - '.$request->salestage);

        $name = $sale_stage;
        if($sale_stage == 'Closed Won' || $sale_stage == 'Closed Lost')
                $name = ($sale_stage == 'Closed Won')? 'Cerrada-Ganada':'Cerrada-Perdida';
      
        
        if($search != null){    
            $info = $distro->getPotencial($idDistroPot);     

            $account = array();
            if($info['related_to'] != ''){
                $account = $distro->getAccountId($info['related_to']);

            }

            $nit = (isset($account))? $account['siccode']:'';


            foreach($search as $out){

                $updateinfo = array(

                    'id'                => $out['id'],
                    'cf_1120'           => $name,
                    'amount'            => $info['amount'],
                    'cf_1081'           => $info['leadsource'],
                    'potentialname'     => $info['potentialname'], 
                    'cf_1071'           => (isset($account))? $account['accountname']:'',
                    'cf_1113'           => $nit,
                    'cf_1079'           => $info[$conections['d_kaeser']],   
                    
                );

                if($sale_stage == 'Closed Won' || $sale_stage == 'Closed Lost'){
                    $updateinfo['sales_stage'] = $sale_stage;
                    $updateinfo['closingdate'] = date('Y-m-d');
                }

                $infoupdated = $this->keaser->updatePotential($updateinfo);             

                if(  $out['cf_1113'] != $nit  ){

                    
                        Log::channel('daily')->error('Verify duplicated '.$out['opportunity_type'].' - '.$out['cf_1113']. ' / '.$nit.' ; '.$infoupdated['id']);
                

                        $this->searchduplicated($infoupdated, $nit,   $idDistroPot  ,$potno  ,$crm);


                }
                
                return true;

            }
        }else{

            $id         = $request->id;
            $returned   = $this->createPotential($id, $crm, $potno );

            if($returned != null){

                $this->searchduplicated($returned['result'],$returned['nit'],$id,$potno,$crm);

            }
            return true;

        }


        return true;

    }



    /**
     * [createPotential description]
     * @param  [type] $idDistro  [description]
     * @param  string $crmDistro [description]
     * @return [type]            [description]
     */
    private function createPotential($idDistro, $crmDistro='',$potential_no=''){


        $crm        = $crmDistro;
        $idDistro   = $idDistro;
        
        $conections = config('array.conections.'.$crm);
        $distro = new Datacrm($conections['user'],$conections['token'],$conections['url']);


        Log::channel('daily')->error('Create Potential conection '.$crmDistro.' '.$idDistro);
        
        $searchExist    = $this->keaser->searchPotential( ['nextstep' => $potential_no, 'related_to' => $conections['id_kaeser']] );

        try{
            if($searchExist == null){

                $info           = $distro->getPotencial($idDistro);
                $contacts       = $this->keaser->getContactByAccountId($conections['id_kaeser']);

                $account = array();
                if($info['related_to'] != ''){
                    $account = $distro->getAccountId($info['related_to']);
                }


                $users = array();
                if($info['assigned_user_id'] != ''){
                    $users = $distro->getUserByID($info['assigned_user_id']);
                }

            
                $nit = (isset($account))? $account['siccode']:'';


                $opportunity = 'Aprobado';
                $sale_stage  = 'Nuevo';

                if($nit == ''){
                    $opportunity = 'Error';
                    $sale_stage  = 'Requiere Verificacion';


                    $upDistro = array(
                        'id'                    => $idDistro,
                        'opportunity_type'      => 'Error',
                        'nextstep'              => 'Necesita revisar el NIT'
                        
                    );

                    $distro->updatePotential($upDistro);

                }else{
                    
                    $upDistro = array(
                        'id'                    => $idDistro,
                        'opportunity_type'      => $opportunity,
                        'nextstep'              => ''
                    );
                    $distro->updatePotential($upDistro);

                }


                $name = $info['sales_stage'];
                if($info['sales_stage'] == 'Closed Won' || $info['sales_stage'] == 'Closed Lost')
                        $name = ($info['sales_stage'] == 'Closed Won')? 'Cerrada-Ganada':'Cerrada-Perdida';

            
                $fields = array(
                    'potentialname'     => $info['potentialname'],      // Nombre del Negocio
                    'sales_stage'       => $sale_stage,                     // Fase del Negocio
                    'leadsource'        => 'Distribuidor',              // Origen
                    'description'       => $info['description'],        // Descripcion
                    'assigned_user_id'  => '19x6',      //$contacts['assigned_user_id'],                     // Asignado A     
                    'cf_1113'           => (isset($account))? trim($nit):'',       // NIT
                    'closingdate'       => $info['closingdate'],                            // Fecha de cierre
                    'related_to'        => $conections['id_kaeser'],                              // Id Distriubdor
                    'cf_1079'           => $info[$conections['d_kaeser']],                       // Descripcion Keaser    
                    'cf_1071'           => (isset($account))? $account['accountname']:'',       // Empresa relacionada    
                    'cf_1081'           => $info['leadsource'],                                // Origen negocio distribuidor
                    'cf_1077'           => (isset($users))? $users['first_name'].' '.$users['last_name'] : '', // Contacto
                    'amount'            => $info['amount'],                                                  // Monto   
                    'contact_id'        => $contacts['id'],
                    'nextstep'          => $info['potential_no'],
                    'opportunity_type'  => $opportunity,
                    'cf_1120'           => $name,                     // Fase de venta del distribuidor,

                );
                

                $result = $this->keaser->savePotential($fields);

                $return['result'] = $result;
                $return['nit']    = $nit;  

            }else{

                $return['result'] = $searchExist;
                $return['nit']    = $searchExist['cf_1113'];  

            }
        }catch(\Exception $e){


            Log::channel('daily')->error('Error Creating Potential conection '.$crmDistro.' '.$idDistro.' '.$e->getMessage());

        }    

        return $return;

    }

    /**
     * [searchduplicated description]
     * @param  [type] $result   [description]
     * @param  [type] $nit      [description]
     * @param  [type] $idDistro [description]
     * @param  string $potno    [description]
     * @return [type]           [description]
     */
    private function searchduplicated($result,$nit,$idDistro,$potno='',$crm){

        $conections = config('array.conections.'.$crm);
        $distro = new Datacrm($conections['user'],$conections['token'],$conections['url']);

        Log::channel('daily')->error('Searching duplicated distro -'.$idDistro.' - nit -'.$nit.' crm '.$crm);
 
        $related_to        = $conections['id_kaeser'];


        try{

            if($nit != ''){
     
                $query = "Select * from Potentials where cf_1113 = '".trim($nit)."' and related_to != ".$related_to;
                $duplicates =  $this->keaser->makeQuery($query);  

            }

            if($result == null){

                $searchPot = $this->keaser->searchPotential( ['nextstep' => $potno, 'related_to' => $related_to] );

                if($nit == ''){

                    foreach($searchPot as $pot){
                        $this->nitNull($idDistro,$pot['id'],$distro);
                    }
                    return true;

                }else{

                    foreach($searchPot as $pot){
                        
                        $this->duplicated($duplicates,$idDistro,$distro,$pot,$related_to);
                    }

                }


            }else{

                if($nit == ''){

                    $this->nitNull($idDistro,$result['id'],$distro);
                    return true;

                }else{

                    $this->duplicated($duplicates,$idDistro,$distro,$result,$related_to);
                    return true;

                }


            }
        }catch(\Exception $e){

             Log::channel('daily')->error('Error searchduplicated '.$crm.' '.$idDistro.' '.$e->getMessage().' '.$result);

        }


        return true;

    }




    /**
     * Undocumented function
     *
     * @param [type] $duplicates
     * @param [type] $idDistro
     * @param [type] $distro
     * @param [type] $result
     * @param [type] $related_to
     * @return void
     */

    public function duplicated($duplicates,$idDistro,$distro,$result,$related_to){

        if($duplicates == null ){ 

            Log::channel('daily')->error('Searching search Results - duplicated - null');
            
            $upDistro = array(
                    'id'                    => $idDistro,
                    'opportunity_type'      => 'Aprobado',
                    'nextstep'              => ''
            );

            $distro->updatePotential($upDistro);


            $info = array(
                'id'                => $result['id'],
                'opportunity_type'  => 'Aprobado',

            );

            $this->keaser->updatePotential($info);
            return true;


        }else{

            Log::channel('daily')->error('Searching search Results -duplicated -'.count($duplicates));
            $change = 0;

            foreach($duplicates as $du){


                if($du['related_to'] != $related_to && $du['sales_stage'] != 'Closed Lost' && $du['opportunity_type'] != 'Negado'){

                    Log::channel('daily')->error('compare -'.$du['related_to'].'- with -'.$related_to);

                    $change++;

                    $info = array(
                        'id'                => $result['id'],
                        'sales_stage'       => 'Requiere Verificacion',
                        'opportunity_type'  => 'Requiere Verificacion',
                    );

                    $this->keaser->updatePotential($info);


                    $upDistro = array(
                        'id'                    => $idDistro,
                        'opportunity_type'      => 'Requiere Verificacion',
                        'nextstep'              => ''
                    );

                    $distro->updatePotential($upDistro);
                    return true;

                }

            }

            if($change == 0){

                $upDistro = array(
                    'id'                    => $idDistro,
                    'opportunity_type'      => 'Aprobado',
                    'nextstep'              => ' '
                );

                $distro->updatePotential($upDistro);



                $info = array(
                        'id'                => $result['id'],
                        'opportunity_type'  => 'Aprobado',
                    );

                $this->keaser->updatePotential($info);

            
            }

       }

       return true;
    }




    /**
     * Undocumented function
     *
     * @param [type] $idDistro
     * @param [type] $idPotKaeser
     * @param [type] $distro
     * @return void
     */
    public function nitNull($idDistro,$idPotKaeser,$distro){

        Log::channel('daily')->error('Searching search Results -duplicated - vacio '.$idPotKaeser);
                
        $upDistro = array(
                'id'                    => $idDistro,
                'opportunity_type'      => 'Error',
                'nextstep'              => 'Necesita revisar el NIT'

        );

        $distro->updatePotential($upDistro);


        $info = array(
            'id'                => $idPotKaeser,
            'opportunity_type'  => 'Error',
        );

        $this->keaser->updatePotential($info);


        return true;
        
    }



    /**
     * Undocumented function
     *
     * @param Request $request
     * @return void
     */
    public function deleteKaeser(Request $request){

        $potno             = $request->potentialno; 
        $related_to        = '11x2032';

        $search = $this->keaser->searchPotential( ['nextstep' => $potno, 'related_to' => $related_to] );

        
        if($search != null){

            foreach($search as $out)    
                $this->keaser->deletePotential($out['id']);

        }
        
        return true;

    }




        /**
     * Undocumented function
     *
     * @param Request $request
     * @return void
     */
    public function updateCompany(Request $request){

        $id_company     = $request->company;
        $name_company   = $request->name;
        $nit_company    = $request->nit;  
        $crm            = $request->crm;


        Log::channel('daily')->error('Update Company '.$name_company.' - '.$nit_company.' - '.$id_company);

        $conections = config('array.conections.'.$crm);

        $related_to = $conections['id_kaeser'];

        $distro = new Datacrm($conections['user'],$conections['token'],$conections['url']);


        $list = $distro->searchPotential( ['related_to' => $id_company] );

        if($list != null)
            foreach($list as $out){

                $search = $this->keaser->searchPotential( ['nextstep' => $out['potential_no'], 'related_to' => $related_to] );

                if($search != null)
                    foreach($search as $pots){

                        $info = array(
                            'id'       => $pots['id'],
                            'cf_1113'  => $nit_company,
                            'cf_1071'  => $name_company,
                        );

                        
                        $updated = $this->keaser->updatePotential($info);

                        $returned['result'] = $updated;
                        $returned['nit']    = $nit_company;


                        if($nit_company != $pots['cf_1113'])
                            $this->searchduplicated($updated,$nit_company,$out['id'],$pots['potential_no'],$crm);


                    }

            }

        return true;

        

    }


    
    /**
     * Undocumented function
     *
     * @return void
     */
        public function testExcel(){

        $info = Excel::toCollection(null, storage_path('export2.xlsx'));
        $i=0;
        $j=0;
        $crm = '';    

        foreach($info as $sheet){
        
           
            $j=0;    
            foreach($sheet as $pot){
                
                
                if($j>1 && $i==0 && isset($pot['0'])){

                    try{
                   
                        $dist = 'K Compresores';

                        $crm = $this->crmtoselect($dist);

                        $conections = config('array.conections.'.$crm);
                        $distro = new Datacrm($conections['user'],$conections['token'],$conections['url']);


                        if($distro != null){     

                            $searchExist = $this->keaser->searchPotential( ['nextstep' => $pot['0'], 'related_to' => $conections['id_kaeser']] );

                            if($searchExist == null){

                                $search = $distro->searchbyPotentialNo($pot['0']);

                                Log::channel('daily')->error($search['id'].' = '.$crm.' - '.$pot['0']);

                                $returned = $this->createPotential($search['id'],$crm,$pot['0']);

                                if($returned['nit'] != '')
                                    $this->searchduplicated($returned['result'],$returned['nit'],$search['id'],$pot[0],$crm);   
                            }
                        }

                    }catch(\Exception $e){

                        $msg = ['method' => 'importData', 'crm' => $crm, 'error' => $e->getMessage()];
                        Log::channel('daily')->error(json_encode($msg));

                    }   


                }     
                $j++;
            }
            $i++;  

        }

        return true;
        
    }


    /**
     * 
     */

    public function crmtoselect($name){


        switch($name){

            case 'Ingeasesorias' : return 'ingeasesoriassas'; break;

            case 'Formar TH' : return 'formarth'; break;

            case 'Cazur' : return 'cazur'; break;

            case 'Eqysol' : return 'eqysol'; break;

            case 'GPEN - GESTION DE PROYECTOS ENERGETICOS S.A.S' : return 'gpensas'; break;

            case 'Jargo' : return 'jargo'; break;

            case 'K Compresores' : return 'kcompresores'; break;

            case 'Petrosystems' : return 'petrosystems'; break;

        }
    }



}
