<?php
namespace App;
use PDO;


class Page
{
    private \Twig\Environment $twig;
    public $link;
    public $session;
    

    function __construct()
    {
        $this->session = new Session();
        $loader = new \Twig\Loader\FilesystemLoader('../templates');
        $this->twig = new \Twig\Environment($loader, [
            'cache' => '../var/cache/compilation_cache',
            'debug' => true
        ]);


    try{
        $this->link= new \PDO('mysql:host=mysql;dbname=b2-paris',"root","");
    } catch (\PDOException $e){
        var_dump($e->getMessage());
        die();
    }
    }

    public function insert(string $table_name, array $data)
    {
        $sql = 'INSERT INTO ' . $table_name . '(surname, name, email, password) VALUES (:surname, :name, :email, :password)' ;
        $sth = $this->link->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute($data);
    }

    public function getUserByEmail(array $data)
    {
        $sql ="SELECT * FROM users WHERE email=:email";
        $sth = $this->link->prepare($sql);
        $sth->execute($data);
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function render(string $name, array $data) :string
    {
        return $this->twig->render($name, $data);
    }

    public function generateRandomPassword($length = 10){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomPassword = '';
        for ($i = 0; $i < $length; $i++) {
            $randomPassword .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomPassword;
    }

    public function updateUserPassword(string $email, string $newPassword){
        $sql ="UPDATE users SET password = :newPassword WHERE email=:email";
        $sth = $this->link->prepare($sql);
        $sth->execute([
            'email' => $email,
            'newPassword' => $newPassword
        ]);
    }

    public function getAllCustomers(){
        $sql ="SELECT * FROM users WHERE role='client' and verified=TRUE ";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC); 
    }
    
    public function getAllInterventions(){
        $sql ="SELECT * FROM interventions";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(); 
    }

    public function getAllStandardiste(){
        $sql ="SELECT * FROM users WHERE role='standardiste'";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC); 
    }
    
    public function getAllIntervenent(){
        $sql ="SELECT * FROM users WHERE role='intervenant'";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC); 
    }
    
    public function getAllNewCustormers(){
        $sql ="SELECT * FROM users WHERE role='client' and verified = FALSE ";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC); 
    }

    public function getInterventionInProgressByClient($user_id){
        $sql ="SELECT * FROM interventions WHERE client_id=$user_id AND status_suivi NOT IN ('Clôturée', 'Annulée')";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC); 
    }
    
    // Recherche les interventions
    public function searchInterventions($query) {
        $stmt = $this->link->prepare("SELECT * FROM interventions WHERE id LIKE :query OR client_id LIKE :query OR status_suivi LIKE :query OR degre_urgence LIKE :query OR start_date LIKE :query or infos LIKE :query OR standardiste_id LIKE :query");
        $stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Recherche les clients
    public function searchClients($query) {
        $stmt = $this->link->prepare("SELECT * FROM users WHERE role='client' and (name LIKE :query OR surname LIKE :query OR user_id LIKE :query)");
        $stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Recherche les intervenants
    public function searchIntervenants($query) {
        $stmt = $this->link->prepare("SELECT * FROM users WHERE role='intervenant' and ( name LIKE :query OR surname LIKE :query OR user_id LIKE :query)");
        $stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Recherche les standardistes
    public function searchStandardistes($query) {
        $stmt = $this->link->prepare("SELECT * FROM users WHERE role ='standardiste' and ( name LIKE :query OR surname LIKE :query OR user_id LIKE :query)");
        $stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function addCommentaire(array $data){
        $sql = "INSERT INTO commentaires (user_id,intervention_id, commentaire) 
                VALUES (:user_id, :intervention_id, :commentaire)";

        $stmt = $this->link->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);

        $stmt->execute($data);

    }

    public function getALLComment(){
        $sql ="SELECT c.*, u.name AS user_name, u.surname AS user_surname FROM commentaires c INNER JOIN users u ON c.user_id = u.user_id";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC); 
    }
    

    public function getCommentByIntervention($intervention_id){
        $sql = "SELECT c.*, u.name AS user_name, u.surname AS user_surname
                FROM commentaires c
                INNER JOIN users u ON c.user_id = u.user_id
                WHERE intervention_id = :intervention_id";
    
        $sth = $this->link->prepare($sql);
        $sth->bindParam(':intervention_id', $intervention_id, \PDO::PARAM_INT);
        $sth->execute();
        
        return $sth->fetchAll(\PDO::FETCH_ASSOC); 
    }
    

    public function getInterventionCompletedByClient($user_id){
        $sql ="SELECT * FROM interventions WHERE client_id=$user_id and  status_suivi='Clotûrée' or status_suivi='Annulée' ";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC); 
    }

    public function getInterventionsByIntervenantId($intervenantId) {
        $sql = "SELECT interventions.*
                FROM interventions
                INNER JOIN intervenant_intervention ON interventions.id = intervenant_intervention.intervention_id
                WHERE intervenant_intervention.intervenant_id = :intervenantId";
        
        $stmt = $this->link->prepare($sql);
        $stmt->bindParam(':intervenantId', $intervenantId, \PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getInterventionsAscByIntervenantId($intervenantId) {
        $sql = "SELECT interventions.*
                FROM interventions
                INNER JOIN intervenant_intervention ON interventions.id = intervenant_intervention.intervention_id
                WHERE intervenant_intervention.intervenant_id = :intervenantId ORDER BY user_id ASC";
        
        $stmt = $this->link->prepare($sql);
        $stmt->bindParam(':intervenantId', $intervenantId, \PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function getInterventionsDescByIntervenantId($intervenantId) {
        $sql = "SELECT interventions.*
                FROM interventions
                INNER JOIN intervenant_intervention ON interventions.id = intervenant_intervention.intervention_id
                WHERE intervenant_intervention.intervenant_id = :intervenantId ORDER BY user_id DESC";
        
        $stmt = $this->link->prepare($sql);
        $stmt->bindParam(':intervenantId', $intervenantId, \PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getInterventionsAnnuleeByIntervenantId($intervenantId) {
        $sql = "SELECT interventions.*
                FROM interventions
                INNER JOIN intervenant_intervention ON interventions.id = intervenant_intervention.intervention_id
                WHERE intervenant_intervention.intervenant_id = :intervenantId and interventions.status_suivi='Annulée' ";
        
        $stmt = $this->link->prepare($sql);
        $stmt->bindParam(':intervenantId', $intervenantId, \PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getInterventionsencoursByIntervenantId($intervenantId) {
        $sql = "SELECT interventions.*
                FROM interventions
                INNER JOIN intervenant_intervention ON interventions.id = intervenant_intervention.intervention_id
                WHERE intervenant_intervention.intervenant_id = :intervenantId and interventions.status_suivi='En cours' ";
        
        $stmt = $this->link->prepare($sql);
        $stmt->bindParam(':intervenantId', $intervenantId, \PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getInterventionsenAttenteByIntervenantId($intervenantId) {
        $sql = "SELECT interventions.*
                FROM interventions
                INNER JOIN intervenant_intervention ON interventions.id = intervenant_intervention.intervention_id
                WHERE intervenant_intervention.intervenant_id = :intervenantId and interventions.status_suivi='En attente' ";
        
        $stmt = $this->link->prepare($sql);
        $stmt->bindParam(':intervenantId', $intervenantId, \PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getIntervenantByIntervention($id) {
        $sql = "SELECT u.name, u.surname
                FROM intervenant_intervention ii
                INNER JOIN users u ON ii.intervenant_id = u.user_id
                WHERE ii.intervention_id = :id";
        $sth = $this->link->prepare($sql);
        $sth->bindParam(':id', $id, \PDO::PARAM_INT);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC); 
    }

    public function updateStatusIntervention($id,$status){
        $sql ="UPDATE interventions SET status_suivi =:status  WHERE id = :id";
        $sth = $this->link->prepare($sql);
        $sth->bindParam(':status',$status);
        $sth->bindParam(':id',$id);
        $sth->execute();
        return true;
    }

    public function updateUrgenceIntervention($id,$urgence){
        $sql ="UPDATE interventions SET degre_urgence =:urgence  WHERE id = :id";
        $sth = $this->link->prepare($sql);
        $sth->bindParam(':urgence',$urgence);
        $sth->bindParam(':id',$id);
        $sth->execute();
        return true;
    }
    public function getInterventionsByClient($id){
        $sql ="SELECT * FROM interventions WHERE client_id=$id";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }
    
    public function getInterventionsByID($id){
        $sql ="SELECT * FROM interventions WHERE id=$id";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetch(\PDO::FETCH_ASSOC);

    }

    public function getUserByID($id) {
        $sql ="SELECT * FROM users WHERE user_id=$id";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getNewCustomerByID($id){
        $sql ="SELECT * FROM users WHERE user_id=$id and verified=FALSE";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateUserRole($id,$role){
        $sql ="UPDATE users SET role = :role WHERE user_id = :id";
        $sth = $this->link->prepare($sql);
        $sth->bindParam(':role',$role);
        $sth->bindParam(':id',$id);
        $sth->execute();
        return true;
    }


    public function setUserVerified($id){
        $sql ="UPDATE users SET verified = TRUE WHERE user_id = :id";
        $sth = $this->link->prepare($sql);
        $sth->bindParam(':id',$id);
        $sth->execute();
        return true;
    }

    public function insertIntervention(array $data) {
        // Requête SQL pour l'insertion des données de l'intervention
        $sql = "INSERT INTO interventions (client_id, standardiste_id, start_date, degre_urgence, infos) 
                VALUES (:client_id, :standardiste_id, :start_date, :degre_urgence, :infos)";

        // Préparation de la requête SQL
        $stmt = $this->link->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);

        // Exécution de la requête SQL
        $stmt->execute($data);

        return $this->link->lastInsertId();

    }

    public function insertIntervenantIntervention($intervenantId, $interventionId) {
        $sql = "INSERT INTO intervenant_intervention (intervenant_id, intervention_id) VALUES (:intervenant_id, :intervention_id)";
        $stmt = $this->link->prepare($sql);
        $stmt->bindParam(':intervenant_id', $intervenantId);
        $stmt->bindParam(':intervention_id', $interventionId);
        $stmt->execute();
    }

    public function getInterventionByStandardiste($id){
        $sql ="SELECT * FROM interventions WHERE standardiste_id=$id";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    // Dans la classe Page

    public function getInterventionAsc()
    {
        $sql = "SELECT * FROM interventions ORDER BY id ASC";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getInterventionDesc()
    {
        $sql = "SELECT * FROM interventions ORDER BY id DESC";
        $sth = $this->link->prepare($sql);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }


// Dans la classe Page

    // Tri des clients
    public function getClientsAsc()
    {
        $query = "SELECT * FROM users WHERE role='client' and verified=TRUE ORDER BY user_id ASC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getClientsDesc()
    {
        $query = "SELECT * FROM users WHERE role='client' and verified=TRUE ORDER BY user_id DESC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getClientsNameAsc()
    {
        $query = "SELECT * FROM users WHERE role='client' and verified=TRUE ORDER BY name ASC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getClientNameDesc()
    {
        $query = "SELECT * FROM users WHERE role='client' and verified=TRUE ORDER BY name DESC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    // Tri des intervenants
    public function getIntervenantAsc()
    {
        $query = "SELECT * FROM users WHERE role='intervenant' ORDER BY user_id ASC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getIntervenantDesc()
    {
        $query = "SELECT * FROM users WHERE role='intervenant' ORDER BY user_id DESC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getIntervenantNameAsc()
    {
        $query = "SELECT * FROM users WHERE role='intervenant' ORDER BY name ASC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getIntervenantNameDesc()
    {
        $query = "SELECT * FROM users WHERE role='intervenant' ORDER BY name DESC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    // Tri des standardistes
    public function getStandardisteAsc()
    {
        $query = "SELECT * FROM users WHERE role='standardiste' ORDER BY user_id ASC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getStandardisteDesc()
    {
        $query = "SELECT * FROM users WHERE role='standardiste' ORDER BY user_id DESC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getStandardisteNameAsc()
    {
        $query = "SELECT * FROM users WHERE role='standardiste' ORDER BY name ASC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getStandardisteNameDesc()
    {
        $query = "SELECT * FROM users WHERE role='standardiste' ORDER BY name DESC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    // Tri des nouveaux clients
    public function getNewClientAsc()
    {
        $query = "SELECT * FROM users WHERE role='client' and verified=FALSE ORDER BY user_id ASC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getNewClientDesc()
    {
        $query = "SELECT * FROM users WHERE role='client' and verified=FALSE ORDER BY user_id DESC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getNewClientNameAsc()
    {
        $query = "SELECT * FROM users WHERE role='client' and verified=FALSE ORDER BY name ASC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getNewClientNameDesc()
    {
        $query = "SELECT * FROM users WHERE role='client' and verified=FALSE ORDER BY name DESC";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getInterventionCompletedByintervenant($id){
        $sql = "SELECT interventions.*
        FROM interventions
        INNER JOIN intervenant_intervention ON interventions.id = intervenant_intervention.intervention_id
        WHERE intervenant_intervention.intervenant_id = :intervenantId and interventions.status_suivi='Clôturée'";

        $stmt = $this->link->prepare($sql);
        $stmt->bindParam(':intervenantId', $intervenantId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getInterventionCompleted(){
        $query = "SELECT * FROM interventions WHERE status_suivi='Clôturée'";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getInterventionAnnulee(){
        $query = "SELECT * FROM interventions WHERE status_suivi='Annulée'";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getInterventionAttente(){
        $query = "SELECT * FROM interventions WHERE status_suivi='En attente'";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }

    public function getInterventionEnCours(){
        $query = "SELECT * FROM interventions WHERE status_suivi='En cours'";
        $sth = $this->link->prepare($query);
        $sth->execute();
        return $sth->fetchALL(\PDO::FETCH_ASSOC);
    }




}