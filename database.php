<?php

class xs_documentation_database
{
        private $conn = NULL;
        
        function __construct()
        {
                $this->init_db();
        }

        function init_db()
        {
                if(isset($this->conn))
                        return;
                
                $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

                if (mysqli_connect_error()) {
                        die("Connection to database failed: " . mysqli_connect_error());
                }
                if(is_resource($this->conn)) { 
                        $this->conn->query($this->conn, "SET NAMES 'utf8'"); 
                        $this->conn->query($this->conn, "SET CHARACTER SET 'utf8'"); 
                } 
                
                $result = $this->conn->query("SELECT 1 FROM `xs_documentation` LIMIT 1");
                if($result === FALSE)
                        $this->conn->query("CREATE TABLE xs_documentation ( 
                                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `name` VARCHAR(64) NOT NULL,
                                `product` VARCHAR(64) NOT NULL,
                                `lang` VARCHAR(16) NOT NULL,
                                `title` VARCHAR(64) NOT NULL,
                                `text` TEXT NOT NULL,
                                `file` VARCHAR(256) NOT NULL,
                                `create_by` INT(11) NOT NULL,
                                `create_date` INT(11) NOT NULL,
                                `modify_date` INT(11) NOT NULL
                                );"
                        );
        }
        
        function execute_query($sql_query)
        {
                $offset = $this->conn->query($sql_query);
                if (!$offset) {
                        echo "Could not run query: SQL_ERROR -> " . $this->conn->error . " SQL_QUERY -> " . $sql_query;
                        exit;
                }
                return $offset;
        }
        
        function get_fields($skip = NULL)
        {
                $offset = array();
                $fields = array('id','name','product','lang','title','text','create_by','create_date','modify_date');
                if(empty($skip)) 
                        return $fields;
                        
                foreach($fields as $single)
                        if(!in_array($single, $skip))
                                $offset[] = $single;
                
                return $offset;
        }
        
        function get_users_table()
        {
                global $wpdb;
                return $wpdb->prefix . "users";
        }
        
        function get($query = array()) 
        {
                $default = array(
                        'id' => '', 
                        'name' => '',
                        'product' => '', 
                        'lang' => '', 
                        'create_by' => '',
                );
                
                $query += $default;
                
                $id = empty($query['id']) ? '' : ' AND xs_documentation.id="'.sanitize_text_field($query['id']).'"';
                $name = empty($query['name']) ? '' : ' AND xs_documentation.name="'.sanitize_text_field($query['name']).'"';
                $product = empty($query['product']) ? '' : ' AND xs_documentation.product="'.sanitize_text_field($query['product']).'"';
                $lang = empty($query['lang']) ? '' : ' AND xs_documentation.lang="'.sanitize_text_field($query['lang']).'"';
                $create_by = empty($query['create_by']) ? '' : ' AND xs_documentation.create_by="'.sanitize_text_field($query['create_by']).'"';
                $offset = array();
                
                $user_table = $this->get_users_table();
                $sql = "SELECT xs_documentation.id AS id, 
                        xs_documentation.name AS name,
                        xs_documentation.product AS product,
                        xs_documentation.lang AS lang,
                        xs_documentation.title AS title,
                        xs_documentation.text AS text,
                        xs_documentation.file AS file,
                        users_tbl.display_name AS create_by,
                        FROM_UNIXTIME(xs_documentation.create_date) AS 'create_date',
                        FROM_UNIXTIME(xs_documentation.modify_date) AS 'modify_date'
                        FROM xs_documentation
                                JOIN ".$user_table." AS users_tbl
                                ON xs_documentation.create_by = users_tbl.ID
                        WHERE 1=1". $id . $name . $product . $lang . $create_by;

                $result = $this->execute_query($sql);
                
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                $offset[]= $row;
                        }
                }
                $result->close();
                return $offset;
        }
        function get_meta($query = array()) 
        {
                $default = array(
                        'id' => '', 
                        'name' => '',
                        'product' => '', 
                        'lang' => '', 
                        'create_by' => '',
                );
                
                $query += $default;
                
                $id = empty($query['id']) ? '' : ' AND xs_documentation.id="'.sanitize_text_field($query['id']).'"';
                $name = empty($query['name']) ? '' : ' AND xs_documentation.name="'.sanitize_text_field($query['name']).'"';
                $product = empty($query['product']) ? '' : ' AND xs_documentation.product="'.sanitize_text_field($query['product']).'"';
                $lang = empty($query['lang']) ? '' : ' AND xs_documentation.lang="'.sanitize_text_field($query['lang']).'"';
                $create_by = empty($query['create_by']) ? '' : ' AND xs_documentation.create_by="'.sanitize_text_field($query['create_by']).'"';
                $offset = array();
                
                $user_table = $this->get_users_table();
                $sql = "SELECT xs_documentation.id AS id,
                        xs_documentation.name AS name,
                        xs_documentation.product AS product,
                        xs_documentation.lang AS lang,
                        xs_documentation.title AS title,
                        xs_documentation.file AS file,
                        users_tbl.display_name AS create_by,
                        FROM_UNIXTIME(xs_documentation.create_date) AS 'create_date',
                        FROM_UNIXTIME(xs_documentation.modify_date) AS 'modify_date'
                        FROM xs_documentation
                                JOIN ".$user_table." AS users_tbl
                                ON xs_documentation.create_by = users_tbl.ID
                        WHERE 1=1". $id . $name . $product . $lang . $create_by;

                $result = $this->execute_query($sql);
                
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                $offset[]= $row;
                        }
                }
                $result->close();
                return $offset;
        }
        
        function get_products_name()
        {
                $sql = "SELECT name,title FROM xs_products WHERE lang='en'"; //FIXME: FORCE LANG EN
                        
                $result = $this->execute_query($sql);
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                $offset[$row['name']] = $row['title'];
                        }
                }
                $result->close();
                return $offset;
        }
        
        function add($input)
        {
                $default = array(
                        'product' => NULL, 
                        'name' => '',
                        'lang' => NULL,
                        'title' => NULL,
                        'text' => '',
                        'file' => '',
                        'create_by' => get_current_user_id(),
                        'modify_date' => time(), 
                        'create_date' => time()
                );
                
                $input += $default;
                
                $sql = 'INSERT INTO xs_documentation (
                name,
                product, 
                lang,
                title, 
                text, 
                file,
                create_by,
                create_date, 
                modify_date
                ) VALUES (?,?,?,?,?,?,?,?,?)';
                
                $query = $this->conn->prepare($sql);
                
                if($query === false) {
                        echo 'Wrong SQL: ' . $sql . ' Error: ' . $this->conn->errno . ' ' . $this->conn->error;
                }
                
                $query->bind_param(
                "ssssssiii", 
                $input['name'],
                $input['product'],
                $input['lang'],
                $input['title'], 
                $input['text'],
                $input['file'],
                $input['create_by'],
                $input['create_date'], 
                $input['modify_date']
                );
                
                if(!$query->execute()) {
                        echo "Could not run query: SQL_ERROR -> " . $query->error . " SQL_QUERY -> " . $sql;
                        exit;
                }
                $query->close();
                
        }
        
        function update($input)
        {
                foreach($input as $single)
                        $this->update_single($single, $single['id']);
        }
        
        function update_single($single, $id)
        {
                $id = sanitize_text_field($id);
                $sql = "SELECT name,product,lang,title,text,file,modify_date FROM xs_documentation WHERE id=". $id ;

                $result = $this->execute_query($sql);
                $default = $result->fetch_assoc();
                $result->close();
                
                $single += $default;
                $single['modify_date'] = time();


                $sql = 'UPDATE xs_documentation SET name=?,product=?,lang=?,title=?,text=?,file=?,modify_date=? WHERE id=?';
                
                $query = $this->conn->prepare($sql);
                
                if($query === false) {
                        trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $this->conn->errno . ' ' . $this->conn->error, E_USER_ERROR);
                }
                $query->bind_param(
                        "ssssssii", 
                        $single['name'],
                        $single['product'], 
                        $single['lang'],
                        $single['title'],
                        $single['text'], 
                        $single['file'],
                        $single['modify_date'], 
                        $id
                );
                
                if(!$query->execute()) {
                        echo "Could not run query: SQL_ERROR -> " . $query->error . " SQL_QUERY -> " . $sql;
                        exit;
                }
                $query->close();
        }
        
        function remove($id)
        {
                $sql = 'DELETE FROM xs_documentation WHERE `id`=?';
                $query = $this->conn->prepare($sql);
                
                if($query === false) {
                        trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $this->conn->errno . ' ' . $this->conn->error, E_USER_ERROR);
                }
                $query->bind_param("i", $id);
                if(!$query->execute()) {
                        echo "Could not run query: SQL_ERROR -> " . $query->error . " SQL_QUERY -> " . $sql;
                        exit;
                }
                
                $query->close();
        }
}

?>
