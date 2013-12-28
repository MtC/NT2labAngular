<?php
namespace Mptt;

class Mptt {
	protected $_pdo;
	protected $_properties	= array();
	protected $_errors		= array();

    function __construct($pdo) {
		$this->_pdo = $pdo;
	}
	
	public function setProperties($table_name = 'mptt', $title_column = 'title', $parent_column = 'parent') {
        $_table_name = $table_name != 'mptt' ? 'mptt_'.str_replace('mptt_','',$table_name) : 'mptt'; 
		$this->_properties = array(
			'table_name'    =>  $_table_name,
			'id_column'     =>  'id',
			'title_column'  =>  $title_column,
			'left_column'   =>  'lft',
			'right_column'  =>  'rgt',
			'parent_column' =>  $parent_column
		);
	}
    
    public function addPropertie($name = false, $type = 'varchar(20)', $null = 'NOT NULL', $default = "default ''", $after = '') {
        if ($name) {
            $_sql = "ALTER TABLE {$this->_properties['table_name']}"
            . " ADD $name $type $null $default $after";
        }
        $this->_pdo->exec($_sql);
    }

	public function createTable($table = false) {
        if ($table) {
            $this->setProperties($table);
        }
        if (count($this->_properties) == 0) {
            $this->setProperties();
        }
		$_sql = "CREATE TABLE IF NOT EXISTS {$this->_properties['table_name']} (
					id int(11) NOT NULL auto_increment,
					{$this->_properties['title_column']} varchar(50) NOT NULL default '',
					lft int(11) NOT NULL default '0',
					rgt int(11) NOT NULL default '0',
					{$this->_properties['parent_column']} int(11) NOT NULL default '0',
					PRIMARY KEY  (id)
				 ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
				 echo $_sql;
		$this->_pdo->exec($_sql);
	}
    
    public function changeTable($aChange) {
        $_aRestricted   = ['id','title','rgt','lft','parent'];
        $_columns       = $this->getColumns();
        if (is_array($aChange)) {
            foreach ($aChange as $sTable => $aParam) {
                
            }
        }
    }
	
    /**
     * get columns of initiated table
     * @return  array   [0 => 'id',1 => ...]
     */
	public function getColumns() {
        $aResult = [];
        $_sSql   = "SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_NAME = '{$this->_properties['table_name']}'";
        foreach ($this->_pdo->query($_sSql) as $_aRow) {
            $aResult[]    = $_aRow['COLUMN_NAME'];
        }
        return $aResult;
	}
	
    /**
     * get id of given title
     * @param type $sTitle  title from mptt table
     * @return intid id from mptt table by title
     */
	public function getIdByTitle($sTitle) {
		$_sSql	= "SELECT * FROM {$this->_properties['table_name']} WHERE {$this->_properties['title_column']} = :title";
		$_oStmt	= $this->_pdo->prepare($_sSql);
		$_oStmt->bindParam(':title',$sTitle,\PDO::PARAM_STR);
		$_oStmt->execute();
		return $_oStmt->fetchColumn();
	}

    /**
     * the direct children of the given id
     * @param type $iId parent id
     * @return array    mptt table rows with id as parent
     */
    public function getChildren($iId) {
        $_sql   = "SELECT title,output FROM {$this->_properties['table_name']} WHERE parent = :id";
        $_oStmt = $this->_pdo->prepare($_sql);
        $_oStmt->bindParam(':id',$iId,\PDO::PARAM_INT);
        $_oStmt->execute();
        return $_oStmt->fetchAll();
    }
    
	public function getErrors() {
		return $this->_errors;
	}
	
	public function getLastError() {
		$_count	= count($this->_errors);
		if ($_count) return $this->_errors[$_count - 1];
		return false;
	}
    
    public function getItemById($id) {
        $_sql	= "SELECT * FROM {$this->_properties['table_name']} WHERE id = :id";
		$_stmt	= $this->_pdo->prepare($_sql);
		$_stmt->bindParam(':id',$id,\PDO::PARAM_STR);
		$_stmt->execute();
		$_result= $_stmt->fetch();
        return $_result;
    }

    public function add($parent, $title, $position = false) {
        $this->_init();
        $_parent = (int)$parent;
		$_title		= mysql_real_escape_string($title);
        if ($_parent == 0 || isset($this->lookup[$_parent])) {
            $_children = $this->get_children($_parent, true);
            if ($position === false) {
                $_position = count($_children);
            } else {
                $_position = (int)$position;
                if ($_position > count($_children) || $_position < 0) {
                    $_position = count($_children);
                }
            }
            if (empty($_children) || $_position == 0) {
                $_boundary = isset($this->lookup[$_parent]) ? $this->lookup[$_parent][$this->_properties['left_column']] : 0;
            } else {
                $_slice = array_slice($_children, $_position - 1, 1);
                $_children = array_shift($_slice);
                $_boundary = $_children[$this->_properties['right_column']];
            }
            foreach ($this->lookup as $_id => $_properties) {
                if ($_properties[$this->_properties['left_column']] > $_boundary) {
                    $this->lookup[$_id][$this->_properties['left_column']] += 2;
                }
                if ($_properties[$this->_properties['right_column']] > $_boundary) {
                    $this->lookup[$_id][$this->_properties['right_column']] += 2;
                }
            }
			$this->_pdo->exec("LOCK TABLE {$this->_properties['table_name']} WRITE");
			$_sql	= "UPDATE {$this->_properties['table_name']} 
					   SET {$this->_properties['left_column']} = {$this->_properties['left_column']} + 2 
					   WHERE {$this->_properties['left_column']} > $_boundary";
			$_stmt	= $this->_pdo->prepare($_sql);
			$_stmt->execute();
			
			$_sql	= "UPDATE {$this->_properties['table_name']} 
					   SET {$this->_properties['right_column']} = {$this->_properties['right_column']} + 2 
					   WHERE {$this->_properties['right_column']} > $_boundary";
			$_stmt	= $this->_pdo->prepare($_sql);
			$_stmt->execute();

            // insert the new node into the database
			$_lft	= $_boundary + 1;
			$_rgt	= $_boundary + 2;
			$_sql	= "INSERT INTO {$this->_properties['table_name']} 
							({$this->_properties['title_column']},{$this->_properties['left_column']},{$this->_properties['right_column']},{$this->_properties['parent_column']})
							VALUES (:title,:lft,:rgt,:parent)";
			$_stmt	= $this->_pdo->prepare($_sql);
			$_stmt->bindParam(':title',$_title,\PDO::PARAM_STR);
			$_stmt->bindParam(':lft',$_lft,\PDO::PARAM_INT);
			$_stmt->bindParam(':rgt',$_rgt,\PDO::PARAM_INT);
			$_stmt->bindParam(':parent',$_parent,\PDO::PARAM_INT);
			$_stmt->execute();
			
            // get the ID of the newly inserted node
            $_node_id = $this->_pdo->lastInsertId(); 

            // release table lock
			$this->_pdo->exec("UNLOCK TABLES");

            // add the node to the lookup array
            $this->lookup[$_node_id] = array(
                $this->_properties['id_column']      => $_node_id,
                $this->_properties['title_column']   => $_title,
                $this->_properties['left_column']    => $_lft,
                $this->_properties['right_column']   => $_rgt,
                $this->_properties['parent_column']  => $_parent,
            );

            // reorder the lookup array
            $this->_reorder_lookup_array();

            // return the ID of the newly inserted node
            return $_node_id;
        }

        // if script gets this far, something must've went wrong so we return false
        return false;

    }

	//deze functie mag later, kopi�ren is niet nodig voor de eerste tests!
    function copy($source, $target, $position = false) {
    
        // lazy connection: touch the database only when the data is required for the first time and not at object instantiation
        $this->_init();

        // continue only if
        if (

            // source node exists in the lookup array AND
            isset($this->lookup[$source]) &&

            // target node exists in the lookup array OR is 0 (indicating a topmost node)
            (isset($this->lookup[$target]) || $target == 0)

        ) {

            // get the source's children nodes (if any)
            $source_children = $this->get_children($source);

            // this array will hold the items we need to copy
            // by default we add the source item to it
            $sources = array($this->lookup[$source]);

            // the copy's parent will be the target node
            $sources[0][$this->properties['parent_column']] = $target;

            // iterate through source node's children
            foreach ($source_children as $child)

                // save them for later use
                $sources[] = $this->lookup[$child[$this->properties['id_column']]];

            // the value with which items outside the boundary set below, are to be updated with
            $source_rl_difference =

                $this->lookup[$source][$this->properties['right_column']] -

                $this->lookup[$source][$this->properties['left_column']]

                + 1;

            // set the boundary - nodes having their "left"/"right" values outside this boundary will be affected by
            // the insert, and will need to be updated
            $source_boundary = $this->lookup[$source][$this->properties['left_column']];

            // get target node's children (no deeper than the first level)
            $target_children = $this->get_children($target, true);

            // if copy is to be inserted in the default position (as the last of the target node's children)
            if ($position === false)

                // give a numerical value to the position
                $position = count($target_children);

            // if a custom position was specified
            else {

                // make sure given position is an integer value
                $position = (int)$position;

                // if position is a bogus number
                if ($position > count($target_children) || $position < 0)

                    // use the default position (the last of the target node's children)
                    $position = count($target_children);

            }

            // we are about to do an insert and some nodes need to be updated first

            // if target has no children nodes OR the copy is to be inserted as the target node's first child node
            if (empty($target_children) || $position == 0)

                // set the boundary - nodes having their "left"/"right" values outside this boundary will be affected by
                // the insert, and will need to be updated
                // if parent is not found (meaning that we're inserting a topmost node) set the boundary to 0
                $target_boundary = isset($this->lookup[$target]) ? $this->lookup[$target][$this->properties['left_column']] : 0;

            // if target has children nodes and/or the copy needs to be inserted at a specific position
            else {

                // find the target's child node that currently exists at the position where the new node needs to be inserted to
                $slice = array_slice($target_children, $position - 1, 1);

                $target_children = array_shift($slice);

                // set the boundary - nodes having their "left"/"right" values outside this boundary will be affected by
                // the insert, and will need to be updated
                $target_boundary = $target_children[$this->properties['right_column']];

            }

            // iterate through the nodes in the lookup array
            foreach ($this->lookup as $id => $properties) {

                // if the "left" value of node is outside the boundary
                if ($properties[$this->properties['left_column']] > $target_boundary)

                    // increment it
                    $this->lookup[$id][$this->properties['left_column']] += $source_rl_difference;

                // if the "right" value of node is outside the boundary
                if ($properties[$this->properties['right_column']] > $target_boundary)

                    // increment it
                    $this->lookup[$id][$this->properties['right_column']] += $source_rl_difference;

            }

            // lock table to prevent other sessions from modifying the data and thus preserving data integrity
            mysql_query('LOCK TABLE ' . $this->properties['table_name'] . ' WRITE');

            // update the nodes in the database having their "left"/"right" values outside the boundary
            mysql_query('

                UPDATE
                    ' . $this->properties['table_name'] . '
                SET
                    ' . $this->properties['left_column'] . ' = ' . $this->properties['left_column'] . ' + ' . $source_rl_difference . '
                WHERE
                    ' . $this->properties['left_column'] . ' > ' . $target_boundary . '

            ');

            mysql_query('

                UPDATE
                    ' . $this->properties['table_name'] . '
                SET
                    ' . $this->properties['right_column'] . ' = ' . $this->properties['right_column'] . ' + ' . $source_rl_difference . '
                WHERE
                    ' . $this->properties['right_column'] . ' > ' . $target_boundary . '

            ');

            // finally, the nodes that are to be inserted need to have their "left" and "right" values updated
            $shift = $target_boundary - $source_boundary + 1;

            // iterate through the nodes that are to be inserted
            foreach ($sources as $id => &$properties) {

                // update "left" value
                $properties[$this->properties['left_column']] += $shift;

                // update "right" value
                $properties[$this->properties['right_column']] += $shift;

                // insert into the database
                mysql_query('
                    INSERT INTO
                        ' . $this->properties['table_name'] . '
                        (
                            ' . $this->properties['title_column'] . ',
                            ' . $this->properties['left_column'] . ',
                            ' . $this->properties['right_column'] . ',
                            ' . $this->properties['parent_column'] . '
                        )
                    VALUES
                        (
                            "' . mysql_real_escape_string($properties[$this->properties['title_column']]) . '",
                            ' . $properties[$this->properties['left_column']] . ',
                            ' . $properties[$this->properties['right_column']] . ',
                            ' . $properties[$this->properties['parent_column']] . '
                        )
                ');

                // get the ID of the newly inserted node
                $node_id = mysql_insert_id();

                // because the node may have children nodes and its ID just changed
                // we need to find its children and update the reference to the parent ID
                foreach ($sources as $key => $value)

                    // if a child node was found
                    if ($value[$this->properties['parent_column']] == $properties[$this->properties['id_column']])

                        // update the reference to the parent ID
                        $sources[$key][$this->properties['parent_column']] = $node_id;

                // update the node's properties with the ID
                $properties[$this->properties['id_column']] = $node_id;

                // update the array of inserted items
                $sources[$id] = $properties;

            }

            // a reference of a $properties and the last array element remain even after the foreach loop
            // we have to destroy it
            unset($properties);

            // release table lock
            mysql_query('UNLOCK TABLES');

            // at this point, we have the nodes in the database but we need to also update the lookup array

            $parents = array();

            // iterate through the inserted nodes
            foreach ($sources as $id => $properties) {

                // if the node has any parents
                if (count($parents) > 0)

                    // iterate through the array of parent nodes
                    while ($parents[count($parents) - 1]['right'] < $properties[$this->properties['right_column']])

                        // and remove those which are not parents of the current node
                        array_pop($parents);

                // if there are any parents left
                if (count($parents) > 0)

                    // the last node in the $parents array is the current node's parent
                    $properties[$this->properties['parent_column']] = $parents[count($parents) - 1]['id'];

                // update the lookup array
                $this->lookup[$properties[$this->properties['id_column']]] = $properties;

                // add current node to the stack
                $parents[] = array(

                    'id'    =>  $properties[$this->properties['id_column']],
                    'right' =>  $properties[$this->properties['right_column']]

                );

            }

            // reorder the lookup array
            $this->_reorder_lookup_array();

            // return the ID of the copy
            return $sources[0][$this->properties['id_column']];

        }

        // if scripts gets this far, return false as something must've went wrong
        return false;

    }

	//deze functie mag later, verwijderen is niet nodig voor de eerste tests!
    function delete($node) {

        // lazy connection: touch the database only when the data is required for the first time and not at object instantiation
        $this->_init();

        // continue only if target node exists in the lookup array
        if (isset($this->lookup[$node])) {

            // get target node's children nodes (if any)
            $children = $this->get_children($node);

            // iterate through target node's children nodes
            foreach ($children as $child)

                // remove node from the lookup array
                unset($this->lookup[$child[$this->properties['id_column']]]);

            // lock table to prevent other sessions from modifying the data and thus preserving data integrity
            mysql_query('LOCK TABLE ' . $this->properties['table_name'] . ' WRITE');

            // also remove nodes from the database
            mysql_query('

                DELETE
                FROM
                    ' . $this->properties['table_name'] . '
                WHERE
                    ' . $this->properties['left_column'] . ' >= ' . $this->lookup[$node][$this->properties['left_column']] . ' AND
                    ' . $this->properties['right_column'] . ' <= ' . $this->lookup[$node][$this->properties['right_column']] . '

            ');

            // the value with which items outside the boundary set below, are to be updated with
            $target_rl_difference =

                $this->lookup[$node][$this->properties['right_column']] -

                $this->lookup[$node][$this->properties['left_column']]

                + 1;

            // set the boundary - nodes having their "left"/"right" values outside this boundary will be affected by
            // the insert, and will need to be updated
            $boundary = $this->lookup[$node][$this->properties['left_column']];

            // remove the target node from the lookup array
            unset($this->lookup[$node]);

            // iterate through nodes in the lookup array
            foreach ($this->lookup as $id => $properties) {

                // if the "left" value of node is outside the boundary
                if ($this->lookup[$id][$this->properties['left_column']] > $boundary)

                    // decrement it
                    $this->lookup[$id][$this->properties['left_column']] -= $target_rl_difference;

                // if the "right" value of node is outside the boundary
                if ($this->lookup[$id][$this->properties['right_column']] > $boundary)

                    // decrement it
                    $this->lookup[$id][$this->properties['right_column']] -= $target_rl_difference;

            }

            // update the nodes in the database having their "left"/"right" values outside the boundary
            mysql_query('

                UPDATE
                    ' . $this->properties['table_name'] . '
                SET
                    ' . $this->properties['left_column'] . ' = ' . $this->properties['left_column'] . ' - ' . $target_rl_difference . '
                WHERE
                    ' . $this->properties['left_column'] . ' > ' . $boundary . '

            ');

            mysql_query('

                UPDATE
                    ' . $this->properties['table_name'] . '
                SET
                    ' . $this->properties['right_column'] . ' = ' . $this->properties['right_column'] . ' - ' . $target_rl_difference . '
                WHERE
                    ' . $this->properties['right_column'] . ' > ' . $boundary . '

            ');

            // release table lock
            mysql_query('UNLOCK TABLES');

            // return true as everything went well
            return true;

        }

        // if script gets this far, something must've went wrong so we return false
        return false;

    }

    public function get_children($parent = 0, $children_only = false) {

        // lazy connection: touch the database only when the data is required for the first time and not at object instantiation
        $this->_init();

		$_parent = $parent;

        // if parent node exists in the lookup array OR we're looking for the topmost nodes
        if (isset($this->lookup[$_parent]) || $_parent === 0) {
		
            $_children = array();

            // get the keys in the lookup array
            $_keys = array_keys($this->lookup);
			
            // iterate through the available keys
            foreach ($_keys as $_item) {
				// node's "left" is higher than parent node's "left" (or, if parent is 0, if it is higher than 0)
				// node's "left" is smaller than parent node's "right" (or, if parent is 0, if it is smaller than PHP's maximum integer value)
				// if we only need the first level children, check if children node's parent node is the parent given as argument
                if ($this->lookup[$_item][$this->_properties['left_column']] > ($_parent !== 0 ? $this->lookup[$_parent][$this->_properties['left_column']] : 0) && $this->lookup[$_item][$this->_properties['left_column']] < ($_parent !== 0 ? $this->lookup[$_parent][$this->_properties['right_column']] : PHP_INT_MAX) && (!$children_only || ($children_only && $this->lookup[$_item][$this->_properties['parent_column']] == $_parent))) {
					
                    // save to array
                    $_children[$this->lookup[$_item][$this->_properties['id_column']]] = $this->lookup[$_item];
				}
				
			}
            // return children nodes
            return $_children;
			
        }
        // if script gets this far, return false as something must've went wrong
        return false;
    }

    function get_children_count($node) {

        // lazy connection: touch the database only when the data is required for the first time and not at object instantiation
        $this->_init();

        // if node exists in the lookup array
        if (isset($this->lookup[$node])) {

            $_result = 0;

            // iterate through all the records in the lookup array
            foreach ($this->lookup as $_id => $_properties)

                // if node is a direct children of the parent node
                if ($this->lookup[$_id][$this->_properties['parent_column']] == $node)

                    // increment the number of direct children
                    $_result++;

            // return the number of direct children nodes
            return $_result;

        }

        // if script gets this far, return false as something must've went wrong
		$this->_errors[]	= 'The node didn\'t exist';
        return false;
    }

    function get_descendants_count($node) {

        // lazy connection: touch the database only when the data is required for the first time and not at object instantiation
        $this->_init();
		
		$_isInt	= is_int($node);
		
        // if parent node exists in the lookup array
        if (isset($this->lookup[$node])) {

            // return the total number of descendant nodes
            return ($this->lookup[$node][$this->_properties['right_column']] - $this->lookup[$node][$this->_properties['left_column']] - 1) / 2;
        }
        // if script gets this far, return false as something must've went wrong
		if (!$_isInt) {
			$this->_errors[] = 'get_decendants_count returns false: $node is not an int';
		} else {
			$this->_errors[] = 'get_decendants_count returns false: $this->lookup[$node] does not exist';
		}
        return false;

    }

    function get_parent($node) {

        // lazy connection: touch the database only when the data is required for the first time and not at object instantiation
        $this->_init();

        // if node exists in the lookup array
        if (isset($this->lookup[$node]))

            // if node has a parent node, return the parent node's properties
            // also, return 0 if the node is a topmost node
            return isset($this->lookup[$this->lookup[$node][$this->_properties['parent_column']]]) ? $this->lookup[$this->lookup[$node][$this->_properties['parent_column']]] : 0;

        // if script gets this far, return false as something must've went wrong
        return false;

    }

    function get_path($node) {

        // lazy connection: touch the database only when the data is required for the first time and not at object instantiation
        $this->_init();

        $_parents = array();

        // if node exists in the lookup array
        if (isset($this->lookup[$node])) {

            // iterate through all the nodes in the lookup array
            foreach ($this->lookup as $_id => $_properties)

                // if
                if (

                    // node is a parent node
                    $_properties[$this->_properties['left_column']] < $this->lookup[$node][$this->_properties['left_column']] &&

                    $_properties[$this->_properties['right_column']] > $this->lookup[$node][$this->_properties['right_column']]

                )

                    // save the parent node's information
                    $_parents[$_properties[$this->_properties['id_column']]] = $_properties;

        }

        // add also the node given as argument
        $_parents[$node] = $this->lookup[$node];

        // return the path to the node
        return $_parents;

    }

    function get_selectables($node = 0, $separator = ' &rarr; ') {
    
        // lazy connection: touch the database only when the data is required for the first time and not at object instantiation
        $this->_init();

        // continue only if
        if (

            // parent node exists in the lookup array OR is 0 (indicating topmost node)
            isset($this->lookup[$node]) || $node == 0

        ) {

            // the resulting array and a temporary array
            $result = $_parents = array();

            // get node's children nodes
            $_children = $this->get_children($node);
            
            // if node is not 0
            if ($node != 0)

                // prepend the item itself to the list
                array_unshift($_children, $this->lookup[$node]);
                
            // iterate through the nodes
            foreach ($_children as $_id => $_properties) {

                // if we find a topmost node
                if ($_properties[$this->_properties['parent_column']] == 0) {

                    // if the $categories variable is set, save the categories we have so far
                    if (isset($_nodes)) $result += $_nodes;

                    // reset the categories and parents arrays
                    $_nodes = $_parents = array();

                }

                // if the node has any parents
                if (count($_parents) > 0) {

                    $_keys = array_keys($_parents);

                    // iterate through the array of parent nodes
                    while (array_pop($_keys) < $_properties[$this->_properties['right_column']])

                        // and remove parents that are not parents of current node
                        array_pop($_parents);

                }

                // add node to the stack of nodes
                $_nodes[$_properties[$this->_properties['id_column']]] = (!empty($_parents) ? str_repeat($separator, count($_parents)) : '') . $_properties[$this->_properties['title_column']];

                // add node to the stack of parents
                $_parents[$_properties[$this->_properties['right_column']]] = $_properties[$this->_properties['title_column']];

            }

            // may not be set when there are no nodes at all
            if (isset($_nodes))

                // finalize the result
                $result += $_nodes;

            // return the resulting array
            return $result;
            
        }
        
        // if the script gets this far, return false as something must've went wrong
        return false;

    }

    function get_tree($iNode = 0) {
        $aResult = $this->get_children($iNode, true);
        if (!$aResult) {
            return false;
        }
        foreach ($aResult as $_iId => $_properties) {
            $aResult[$_iId]['children'] = $this->get_tree($_iId);
        }
        return $aResult;
    }

	//deze functie mag later, verplaatsen is niet nodig voor de eerste tests!
    function move($source, $target, $position = false) {

        // lazy connection: touch the database only when the data is required for the first time and not at object instantiation
        $this->_init();

        // continue only if
        if (

            // source node exists in the lookup array AND
            isset($this->lookup[$source]) &&

            // target node exists in the lookup array OR is 0 (indicating a topmost node)
            (isset($this->lookup[$target]) || $target == 0) &&
            
            // target node is not a child node of the source node (that would cause infinite loop)
            !in_array($target, array_keys($this->get_children($source)))
            
        ) {
        
            // the source's parent node's ID becomes the target node's ID
            $this->lookup[$source][$this->properties['parent_column']] = $target;

            // get source node's children nodes (if any)
            $source_children = $this->get_children($source);

            // this array will hold the nodes we need to move
            // by default we add the source node to it
            $sources = array($this->lookup[$source]);

            // iterate through source node's children
            foreach ($source_children as $child) {

                // save them for later use
                $sources[] = $this->lookup[$child[$this->properties['id_column']]];

                // for now, remove them from the lookup array
                unset($this->lookup[$child[$this->properties['id_column']]]);

            }

            // the value with which nodes outside the boundary set below, are to be updated with
            $source_rl_difference =

                $this->lookup[$source][$this->properties['right_column']] -

                $this->lookup[$source][$this->properties['left_column']]

                + 1;

            // set the boundary - nodes having their "left"/"right" values outside this boundary will be affected by
            // the insert, and will need to be updated
            $source_boundary = $this->lookup[$source][$this->properties['left_column']];

            // lock table to prevent other sessions from modifying the data and thus preserving data integrity
            mysql_query('LOCK TABLE ' . $this->properties['table_name'] . ' WRITE');

            // we'll multiply the "left" and "right" values of the nodes we're about to move with "-1", in order to
            // prevent the values being changed further in the script
            mysql_query('

                UPDATE
                    ' . $this->properties['table_name'] . '
                SET
                    ' . $this->properties['left_column'] . ' = ' . $this->properties['left_column'] . ' * -1,
                    ' . $this->properties['right_column'] . ' = ' . $this->properties['right_column'] . ' * -1
                WHERE
                    ' . $this->properties['left_column'] . ' >= ' . $this->lookup[$source][$this->properties['left_column']] . ' AND
                    ' . $this->properties['right_column'] . ' <= ' . $this->lookup[$source][$this->properties['right_column']] . '

            ');

            // remove the source node from the list
            unset($this->lookup[$source]);

            // iterate through the remaining nodes in the lookup array
            foreach ($this->lookup as $id=>$properties) {

                // if the "left" value of node is outside the boundary
                if ($this->lookup[$id][$this->properties['left_column']] > $source_boundary)

                    // decrement it
                    $this->lookup[$id][$this->properties['left_column']] -= $source_rl_difference;

                // if the "right" value of item is outside the boundary
                if ($this->lookup[$id][$this->properties['right_column']] > $source_boundary)

                    // decrement it
                    $this->lookup[$id][$this->properties['right_column']] -= $source_rl_difference;

            }

            // update the nodes in the database having their "left"/"right" values outside the boundary
            mysql_query('

                UPDATE
                    ' . $this->properties['table_name'] . '
                SET
                    ' . $this->properties['left_column'] . ' = ' . $this->properties['left_column'] . ' - ' . $source_rl_difference . '
                WHERE
                    ' . $this->properties['left_column'] . ' > ' . $source_boundary . '

            ');

            mysql_query('

                UPDATE
                    ' . $this->properties['table_name'] . '
                SET
                    ' . $this->properties['right_column'] . ' = ' . $this->properties['right_column'] . ' - ' . $source_rl_difference . '
                WHERE
                    ' . $this->properties['right_column'] . ' > ' . $source_boundary . '

            ');

            // get children nodes of target node (first level only)
            $target_children = $this->get_children((int)$target, true);
            
            // if node is to be inserted in the default position (as the last of target node's children nodes)
            if ($position === false)

                // give a numerical value to the position
                $position = count($target_children);

            // if a custom position was specified
            else {

                // make sure given position is an integer value
                $position = (int)$position;

                // if position is a bogus number
                if ($position > count($target_children) || $position < 0)

                    // use the default position (as the last of the target node's children)
                    $position = count($target_children);

            }

            // because of the insert, some nodes need to have their "left" and/or "right" values adjusted

            // if target node has no children nodes OR the node is to be inserted as the target node's first child node
            if (empty($target_children) || $position == 0)

                // set the boundary - nodes having their "left"/"right" values outside this boundary will be affected by
                // the insert, and will need to be updated
                // if parent is not found (meaning that we're inserting a topmost node) set the boundary to 0
                $target_boundary = isset($this->lookup[$target]) ? $this->lookup[$target][$this->properties['left_column']] : 0;

            // if target has any children nodes and/or the node needs to be inserted at a specific position
            else {
            
                // find the target's child node that currently exists at the position where the new node needs to be inserted to
                $slice = array_slice($target_children, $position - 1, 1);

                $target_children = array_shift($slice);

                // set the boundary - nodes having their "left"/"right" values outside this boundary will be affected by
                // the insert, and will need to be updated
                $target_boundary = $target_children[$this->properties['right_column']];

            }

            // iterate through the records in the lookup array
            foreach ($this->lookup as $id => $properties) {

                // if the "left" value of node is outside the boundary
                if ($properties[$this->properties['left_column']] > $target_boundary)

                    // increment it
                    $this->lookup[$id][$this->properties['left_column']] += $source_rl_difference;

                // if the "left" value of node is outside the boundary
                if ($properties[$this->properties['right_column']] > $target_boundary)

                    // increment it
                    $this->lookup[$id][$this->properties['right_column']] += $source_rl_difference;

            }

            // update the nodes in the database having their "left"/"right" values outside the boundary
            mysql_query('

                UPDATE
                    ' . $this->properties['table_name'] . '
                SET
                    ' . $this->properties['left_column'] . ' = ' . $this->properties['left_column'] . ' + ' . $source_rl_difference . '
                WHERE
                    ' . $this->properties['left_column'] . ' > ' . $target_boundary . '

            ');

            mysql_query('

                UPDATE
                    ' . $this->properties['table_name'] . '
                SET
                    ' . $this->properties['right_column'] . ' = ' . $this->properties['right_column'] . ' + ' . $source_rl_difference . '
                WHERE
                    ' . $this->properties['right_column'] . ' > ' . $target_boundary . '

            ');

            // finally, the nodes that are to be inserted need to have their "left" and "right" values updated
            $shift = $target_boundary - $source_boundary + 1;

            // iterate through the nodes to be inserted
            foreach ($sources as $properties) {

                // update "left" value
                $properties[$this->properties['left_column']] += $shift;

                // update "right" value
                $properties[$this->properties['right_column']] += $shift;

                // add the item to our lookup array
                $this->lookup[$properties[$this->properties['id_column']]] = $properties;

            }

            // also update the entries in the database
            // (notice that we're subtracting rather than adding and that finally we multiply by -1 so that the values
            // turn positive again)
            mysql_query('

                UPDATE
                    ' . $this->properties['table_name'] . '
                SET
                    ' . $this->properties['left_column'] . ' = (' . $this->properties['left_column'] . ' - ' . $shift . ') * -1,
                    ' . $this->properties['right_column'] . ' = (' . $this->properties['right_column'] . ' - ' . $shift . ') * -1
                WHERE
                    ' . $this->properties['left_column'] . ' < 0

            ');

            // finally, update the parent of the source node
            mysql_query('

                UPDATE
                    ' . $this->properties['table_name'] . '
                SET
                    ' . $this->properties['parent_column'] . ' = ' . $target . '
                WHERE
                    ' . $this->properties['id_column'] . ' = ' . $source . '

            ');

            // release table lock
            mysql_query('UNLOCK TABLES');

            // reorder the lookup array
            $this->_reorder_lookup_array();

            // return true as everything went well
            return true;

        }

        // if scripts gets this far, return false as something must've went wrong
        return false;

    }

	//deze functie mag later, verplaatsen is niet nodig voor de eerste tests!
    function to_list($node, $list_type = 'ul', $attributes = '') {

        // if node is an ID, get the children nodes
        //  (when called recursively this is an array)
        if (!is_array($node)) $node = $this->get_tree($node);

        // if there are any elements
        if (!empty($node)) {

            // start generating the output
            $out = '<' . $list_type . ($attributes != '' ? ' ' . $attributes : '') . '>';

            // iterate through each node
            foreach ($node as $key => $elem)

                // generate output and if the node has children nodes, call this method recursively
                $out .= '<li>' . $elem[$this->_properties['id_column']] . ':' . $elem[$this->_properties['title_column']] . (is_array($elem['children']) ? $this->to_list($elem['children'], $list_type) : '') . '</li>';

            // return generated output
            return $out . '</' . $list_type . '>';

        }

    }
    
    function _init() {
		// check if setProperties has been set!
		if (count($this->_properties) == 0) $this->setProperties();
		
        // if the results are not already cached
        if (!isset($this->lookup)) {   
            // fetch data from the database
			$this->lookup = array();
			$_sql	= "SELECT * FROM {$this->_properties['table_name']} ORDER BY {$this->_properties['left_column']}";
			$_stmt	= $this->_pdo->prepare($_sql);
			$_stmt->execute();
			while ($_row = $_stmt->fetch()) {
				$this->lookup[$_row[$this->_properties['id_column']]] = $_row;
			}
        }
    }

    protected function _reorder_lookup_array() {

        // re-order the lookup array

        // iterate through the nodes in the lookup array
        foreach ($this->lookup as $_properties) {

            // create a new array with the name of "left" column, having the values from the "left" column
            ${$this->_properties['left_column']}[] = $_properties[$this->_properties['left_column']];
        }
        // order the array by the left column
        // in the ordering process, the keys are lost
        array_multisort(${$this->_properties['left_column']}, SORT_ASC, $this->lookup);

        $_tmp = array();

        // iterate through the existing nodes
        foreach ($this->lookup as $_properties) {

            // and save them to a different array, this time with the correct ID
            $_tmp[$_properties[$this->_properties['id_column']]] = $_properties;
        }
        // the updated lookup array
        $this->lookup = $_tmp;

    }

}