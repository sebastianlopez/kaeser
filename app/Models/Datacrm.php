<?php

namespace App\Models;

use Salaros\Vtiger\VTWSCLib\WSClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Datacrm extends Model
{
    
    public $username;
    public $token;


    public function __construct($usern, $tkn, $link)
    {
       
        $this->username = $usern;
        $this->token    = $tkn;
        $this->url      = $link;

        $this->vt = new WSClient( $this->url, $this->username, $this->token);
  
    }

    /**
     *  Contacts Modules
     */

    public function getAllContacts($limit =100, $offset = 0){

        $potenicials = $this->vt->entities->findMany('Contacts', [], ['*'], $limit, $offset);
        return $potenicials;

    }    

    /**
     * Return Contact Info by ID
     *
     * @param [type] $id
     * @return void
     */ 
    public function getContactByID($id)
    {
        $client = $this->vt->entities->findOneByID('Contacts', $id, ['*']);

        return $client;
    }


    /**
     * Return Contact Info by Mobile
     *
     * @param [type] $phone
     * @return void
     */
    public function getContactByAccountId($account_id){

        $product = $this->vt->entities->findOne('Contacts',['account_id' => $account_id]);
        return $product;

    }


    /**
     * Return Contact Info by Email
     *
     * @param [type] $email
     * @return void
     */
    public function getContactByEmail($email){

        $product = $this->vt->entities->findOne('Contacts',['email' => $email]);
        return $product;

    }



    /**
     * Returns Contacts by search in select add the fields requiere too return
     *
     * @param [type] $search
     * @param integer $limit
     * @param integer $offset
     * @return void
     */
    public function getContacts($search, $limit = 100, $offset = 0)
    {
        $select = [
            'lastname',             //Nombre Contacto
            'email',
            'mobile',
            'id',
            'assigned_user_id',
            'email',
            'leadsource',           //Origen del prospecto
        ];

        $clients = $this->vt->entities->findMany('Contacts', $search, $select, $limit, $offset);
        return $clients;
    }



    /**
     * Updates Contact information.
     *
     * @param [type] $data
     * @return void
     */
    public function updateContact($data)
    {
        $contact = null;
        
        try {
            
            $contact = $this->vt->entities->updateOne('Contacts', $data['id'], $data);

        } catch (\Exception $e) {

            $msg = ['method' => 'updateContact', 'data' => ['id' => $data['id']], 'error' => $e->getMessage()];
            Log::channel('datacrm')->error(json_encode($msg));

        }

        return $contact;
    }


    /**** Potentials */


    /**
     * Get all potentials paged
     *
     * @param integer $limit
     * @param integer $offset
     * @return void
     */
    public function getallPotencials($limit =100, $offset = 0){

        $potenicials = $this->vt->entities->findMany('Potentials', [], ['*'], $limit, $offset);
        return $potenicials;
    }


    /**
     * Get Potential by ID
     *
     * @param [type] $id
     * @return void
     */
    public function getPotencial($id)
    {
        $quote = $this->vt->entities->findOne('Potentials', ['id' => $id]);
        return $quote;
    }


   /**
    * Searchs posibles duplicates potentiasl using the NIT of the companies field cf_1113
    * 
    * @param [type] $nit
    * @param integer $limit
    * @param integer $offset
    * @return void
    */
    public function searchDuplicated($nit,$limit =100, $offset = 0){

        $potenicials = $this->vt->entities->findMany('Potentials',['cf_1113' => $nit],['*'], $limit, $offset);
        return $potenicials;
    }


    /**
     * Undocumented function
     *
     * @param [type] $ptoN
     * @return void
     */
    public function searchbyPotentialNo($ptoN){

        $product = $this->vt->entities->findOne('Potentials',['potential_no' => $ptoN]);
        return $product;

    }



    /**
     * Creates a new potential
     *
     * @param [type] $info
     * @return void
     */
    public function savePotential($info){

        $potential = null;

        try {

            $potential = $this->vt->entities->createOne('Potentials', $info);
            Log::channel('daily')->error('Saved Potential '.$potential['id']);

        } catch (\Exception $e) {

            $msg = ['method' => 'savePotential', 'data' => ['potentialname' => $info['potentialname']], 'error' => $e->getMessage()];
            Log::channel('daily')->error(json_encode($msg));

        }

        return $potential;

    }


    /**
     * Actualizar un Negocio
     *
     * @return void
     */
    public function updatePotential($info){

        $potential = array();

        try {

            $potential = $this->vt->entities->updateOne('Potentials', $info['id'], $info);
            
        } catch (\Exception $e) {

            $msg = ['method' => 'updatePotential', 'data' => ['id' => $info['id']], 'error' => $e->getMessage()];
            Log::channel('daily')->error(json_encode($msg));
        }

        return $potential;


    }



    public function deletePotential($id){

        $potential = false;

        try {
            $potential = $this->vt->entities->deleteOne('Potentials', $id);
        } catch (\Exception $e) {
            $msg = ['method' => 'deletePotential', 'data' => ['id' => $id], 'error' => $e->getMessage()];
            Log::channel('datacrm')->error(json_encode($msg));
        }

        return $potential;

    }






    /**
     * 
     * Modulo Casos o HelpDesk
     * 
     */

    /**
     * Finds a Case/ticket by ticket_no
     *
     * @param [type] $case
     * @return void
     */
    public function getCase($case){

        $product = $this->vt->entities->findOne('HelpDesk',['ticket_no' => $case]);
        return $product;

    }


    /**
     * Updates info in cases.
     *
     * @param [type] $data
     * @return void
     */
    public function updateCase($data){

        $helpDesk = null;
        
        try {
            
            $helpDesk = $this->vt->entities->updateOne('HelpDesk', $data['id'], $data);

        } catch (\Exception $e) {

            $msg = ['method' => 'updateCase', 'data' => ['id' => $data['id']], 'error' => $e->getMessage()];
            Log::channel('daily')->error(json_encode($msg));

        }

        return $helpDesk;

    }



    /**
     * Accounts
     */

    /**
     * Returns an Account, search by ID
     *
     * @return void
     */
    public function getAccountId($id){

            $client = $this->vt->entities->findOne('Accounts',['id' =>  $id]);
            return $client;

     }



    /**
     * Returns all Accounts paged.
     *
     * @param integer $limit
     * @param integer $offset
     * @return void
     */
    public function getAllAccount($limit =1, $offset = 0){

        $potenicials = $this->vt->entities->findMany('Accounts', [], ['*'], $limit, $offset);
        return $potenicials;
    }



    /**
     * Search in Accounts 
     *
     * @param string $field
     * @param [type] $search
     * @return void
     */
    public function findAccount($field='',$search){

        $account = $this->vt->entities->findOne('Accounts',[$field => $search]);
        return $account;

    }



    /**
     *  Users
     */



    /**
     * Finds user in datacrm por ID.
     *
     * @param $id
     *
     * @return array
     * Created by <Rhiss.net>
     */
    public function getUserByID($id)
    {
        $user = $this->vt->entities->findOne('Users', ['id' => $id], ['id', 'last_name', 'first_name']);
        return $user;
    }




    public function searchPotential($search,$limit =100, $offset = 0){

        $potenicials = $this->vt->entities->findMany('Potentials',$search,['*'], $limit, $offset);
        return $potenicials;

    }



    /**
     * Undocumented function
     *
     * @return void
     */ 
    public function makeQuery($query ){

        try{
            
            return $this->vt->runQuery( $query );

        }catch(\Exception $e) {

            $msg = ['method' => 'makeQuery', 'data' => ['query' => $query], 'error' => $e->getMessage()];
            Log::channel('daily')->error(json_encode($msg));

        }
    
    }



}
