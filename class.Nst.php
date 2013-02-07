<?php

/**
 * Gestion de structure par representation intervallaire (Nested Set Trees)
 * 
 * 
 * 
 * <p>Gestion de structure par representation intervallaire</p>
 * 
 * @name intervallaire
 * @author Powange
 * @link 
 * @copyright Powange 2009
 * @version 1.0.0
 * @package class.NST
 * 
 * References:
 * http://www.sitepoint.com/article/1105/2
 * http://searchdatabase.techtarget.com/tip/1,289483,sid13_gci537290,00.html
 * http://dbforums.com/arch/57/2002/6/400142
 * http://www.edutech.ch/contribution/nstrees
 * 
 * 
 * 
 * Datastructures:
 * ---------------
 * 
 * Handle:
 * key: 'table':	name of the table that contains the tree structure
 *              	key: 'lvalname': name of the attribute (field) that contains the left value
 *              	key: 'rvalname': name of the attribute (field) that contains the right value
 * 
 * Node:
 * key 'l': left value
 * key 'r': right value
 * 
 * 
 * Orientation
 * -----------
 * 
 *	    n0
 *	  / | \
 *  n1  N  n3
 *     / \
 *    n4 n5
 *  
 * directions from the perspective of the node N:
 * n0: up / ancestor
 * n1: previous (sibling)
 * n3: next (sibling)
 * n4: first (child)
 * n5: last (child)
 */

class NST
{
	/****************************************************************************************************************/
	/*								Déclaration des attributs															/
	/**************************************************************************************************************/
	private $TableName='';
	private $LeftValName='';
	private $RightValName='';
	
	/**************************************************************************************************************\
	/*								Fin de déclaration des attributs													\
	/****************************************************************************************************************\
	
	/**
	 * __construct
	 * Initialise les attributs suivant les informations données par l'utilisateur
	 *
	 * @param string $TableName nom de la table
	 * @param string $LeftValName nom de la colonne des bornes gauches
	 * @param string $RightValName nom de la colonne des bornes droites
	 */
	public function __construct($TableName='table', $LeftValName='bg', $RightValName='bd')
	{
		$this->TableName=$TableName;
		$this->LeftValName=$LeftValName;
		$this->RightValName=$RightValName;
	}
	
	/****************************************************************************************************************/
	/*								Bloc : méthodes accesseur														/
	/**************************************************************************************************************/
	public function __get ($nom)
	{
		if (isset ($this->$nom))
			return $this->$nom;
	}

	/****************************************************************************************************************/
	/*								Constructeur d'Arbre															 	/
	/**************************************************************************************************************/

	/* Créer une nouvelle entrée racine et retourne le nœud 'l'=1, 'r'=2. */
	public function nstNewRoot($othercols=null)
	{
		$newnode['l'] = 1;
		$newnode['r'] = 2;
		$this->_insertNew($newnode, $othercols);
		return $newnode;
	}

	/* Créer une nouvelle feuille au debut dans '$node'. */
	public function nstNewFirstChild($node, $othercols=null)
	{
		$newnode['l'] = $node['l']+1;
		$newnode['r'] = $node['l']+2;
		$this->_shiftRLValues($newnode['l'], 2);
		$this->_insertNew($newnode, $othercols);
		return $newnode;
	}

	/* Créer une nouvelle feuille en dernier dans '$node'. */
	public function nstNewLastChild($node, $othercols=null)
	{
		$newnode['l'] = $node['r'];
		$newnode['r'] = $node['r']+1;
		$this->_shiftRLValues($newnode['l'], 2);
		$this->_insertNew($newnode, $othercols);
		return $newnode;
	}

	public function nstNewPrevSibling($node, $othercols=null)
	{
		$newnode['l'] = $node['l'];
		$newnode['r'] = $node['l']+1;
		$this->_shiftRLValues($newnode['l'], 2);
		$this->_insertNew($newnode, $othercols);
		return $newnode;
	}

	public function nstNewNextSibling($node, $othercols=null)
	{
		$newnode['l'] = $node['r']+1;
		$newnode['r'] = $node['r']+2;
		$this->_shiftRLValues($newnode['l'], 2);
		$this->_insertNew($newnode, $othercols);
		return $newnode;
	}


	/****************************************************************************************************************/
	/*								Routines internes																/
	/**************************************************************************************************************/

	/* Ajoute '$delta' à toutes les valeurs L and R qui sont >= '$first'. '$delta' peut aussi être négative. */
	private function _shiftRLValues($first, $delta)
	{
		//print("DECALAGE: ajoute $delta aux bornes >= $first <br/>");
		$sql='UPDATE '.$this->TableName.' SET 
			'.$this->LeftValName.'='.$this->LeftValName.'+'.intval($delta).' 
			WHERE '.$this->LeftValName.'>='.$first;
		mysql_query($sql);
		
		$sql='UPDATE '.$this->TableName.' SET 
			'.$this->RightValName.'='.$this->RightValName.'+'.intval($delta).' 
			WHERE '.$this->RightValName.'>='.$first;
		mysql_query($sql);
	}

	/* Ajoute '$delta' à toutes les valeurs L and R qui sont >= '$first' and <= '$last'. '$delta' peut aussi être négative.
	 * retourne les valeurs first/last décalé dans un array de nœud. */
	private function _shiftRLRange($first, $last, $delta)
	{
		$sql='UPDATE '.$this->TableName.' SET 
			'.$this->LeftValName.'='.$this->LeftValName."+$delta 
			WHERE ".$this->LeftValName.">=$first AND ".$this->LeftValName."<=$last";
		mysql_query($sql);
		
		$sql='UPDATE '.$this->TableName.' SET 
			'.$this->RightValName.'='.$this->RightValName."+$delta 
			WHERE ".$this->RightValName.">=$first AND ".$this->RightValName."<=$last";
		mysql_query($sql);
		
		return array('l'=>$first+$delta, 'r'=>$last+$delta);
	}

	/* Créer une nouvelle entrée. */
	private function _insertNew($node, $othercols=null)
	{
		if(strlen($othercols)>0)
			$othercols .= ',';
		$sql='INSERT INTO '.$this->TableName.' SET 
			'.$othercols
			 .$this->LeftValName.'='.$node['l'].', 
			'.$this->RightValName.'='.$node['r'];
		if(!mysql_query($sql))	$this->_prtError();
	}


	/****************************************************************************************************************/
	/*								Réorganisation de l'Arbre														/
	/**************************************************************************************************************/
	
	/* Toutes les fonction nstMove... retourne la nouvelle position du sous-arbre déplacé. */

	/* déplace le nœud '$src' et tous ses éléments (sous-arborescence) afin d'être le prochain frère de '$dst' */
	public function nstMoveToNextSibling($src, $dst)
	{
		return $this->_moveSubtree($src, $dst['r']+1);
	}

	/* déplace le nœud '$src' et tous ses éléments (sous-arborescence) afin d'être le précédent frère de '$dst'. */
	public function nstMoveToPrevSibling($src, $dst)
	{
		return $this->_moveSubtree($src, $dst['l']);
	}

	/* déplace le nœud '$src' et tous ses éléments (sous-arborescence) afin d'être le premier élément de '$dst'. */
	public function nstMoveToFirstChild($src, $dst)
	{
		return $this->_moveSubtree($src, $dst['l']+1);
	}

	/* déplace le nœud '$src' et tous ses éléments (sous-arborescence) afin d'être le dernier élément de '$dst'. */
	public function nstMoveToLastChild($src, $dst)
	{
		return $this->_moveSubtree($src, $dst['r']);
	}

	/* '$src' est le nœud/sous-arbre, '$to' est sa valeur L de destination */
	private function _moveSubtree($src, $to)
	{ 
		$treesize = $src['r']-$src['l']+1;
		$this->_shiftRLValues($to, $treesize);
		// 	$src a été décalé aussi?
		if($src['l'] >= $to){
			$src['l'] += $treesize;
			$src['r'] += $treesize;
		}
		/* now there's enough room next to target to move the subtree*/
		$newpos = 
		$this->_shiftRLRange($src['l'], $src['r'], $to-$src['l']);
		/* correct values after source */
		$this->_shiftRLValues($src['r']+1, -$treesize);
		// 	dest a été décalé aussi?
		if($src['l'] <= $to){
			$newpos['l'] -= $treesize;
			$newpos['r'] -= $treesize;
		}
		return $newpos;
	}

	/****************************************************************************************************************/
	/*								Destructeurs d'arbres															/
	/**************************************************************************************************************/
	
	/* supprime l'ensemble de l'arborescence, y compris tous les dossiers. */
	public function nstDeleteTree ()
	{
		$sql='DELETE FROM '.$this->TableName;
		if(!mysql_query($sql)) {$this->_prtError();}
	}

	/* supprime le nœud '$node' et de tous ses éléments (sous-arbres). */
	public function nstDelete($node)
	{
		$leftanchor = $node['l'];
		
		$sql='DELETE FROM '.$this->TableName.' 
			WHERE 
				'.$this->LeftValName.'>='.$node['l'].' 
				AND '.$this->RightValName.'<='.$node['r'];
		if(!mysql_query($sql)) {$this->_prtError();}
		
		$this->_shiftRLValues($node['r']+1, $node['l'] - $node['r'] -1);
		
		$whereclause=$this->LeftValName.'<'.$leftanchor.'
					ORDER BY '.$this->LeftValName.' DESC';
		return $this->nstGetNodeWhere($whereclause);
	}

	/****************************************************************************************************************/
	/*								Requêtes d'arbres																/
	/**************************************************************************************************************/
	/* Les fonctions suivantes retournent un nœud en cours de validité (valeur L et R),
	/* ou L = 0, R = 0 si le résultat n'existe pas.
	/* ******************************************************************* */

	/* Renvoie le premier nœud qui correspond au '$whereClause'.
		La clause 'WHERE' peut éventuellement aussi contenir les clauses 'ORDER BY' ou 'LIMIT'.
		'$othercols' peut contenir les autres colonnes a récupérer du nœud */
	public function nstGetNodeWhere($whereclause, $othercols=null)
	{
		$noderes['l'] = 0;
		$noderes['r'] = 0;
		
		if(!empty($othercols))
			$othercols .= ', ';
		
		$sql='SELECT 
			'.$othercols.'
			'.$this->LeftValName.' AS l, 
			'.$this->RightValName.' AS r 
			FROM '.$this->TableName.' 
			WHERE '.$whereclause;
		$res = mysql_query($sql);
		
		if(!$res) {$this->_prtError();}
		else {
			if($noderes = mysql_fetch_array ($res)) {
				return $noderes;
			}
		}
		
		return false;
	}
	
	/* Renvoie le nœud qui a pour LeftValName la valeur '$leftval'.  */
	public function nstGetNodeWhereLeft($leftval, $othercols=null)
	{
		return $this->nstGetNodeWhere($this->LeftValName.'='.$leftval, $othercols);
	}
	
	/* Renvoie le nœud qui a pour RightValName la valeur '$rightval'.  */
	public function nstGetNodeWhereRight($rightval, $othercols=null)
	{
		return $this->nstGetNodeWhere($this->RightValName.'='.$rightval, $othercols);
	}
	
	/* Renvoie le nœud racine. */
	public function nstRoot($othercols=null)
	{
		return $this->nstGetNodeWhere($this->LeftValName.'=1', $othercols, $othercols);
	}
	
	/* Renvoie le premier element du nœud '$node'. */
	public function nstFirstChild($node, $othercols=null)
	{
		return $this->nstGetNodeWhere($this->LeftValName.'='.($node['l']+1), $othercols);
	}
	
	/* Renvoie le dernier élément du nœud '$node'. */
	public function nstLastChild($node, $othercols=null)
	{
		return $this->nstGetNodeWhere($this->RightValName.'='.($node['r']-1), $othercols);
	}
	
	/* Renvoie le précèdent frère du nœud '$node'. */
	public function nstPrevSibling($node, $othercols=null)
	{
		return $this->nstGetNodeWhere($this->RightValName.'='.($node['l']-1), $othercols);
	}
	
	/* Renvoie le frère suivant du nœud '$node'. */
	public function nstNextSibling($node, $othercols=null)
	{
		return $this->nstGetNodeWhere($this->LeftValName.'='.($node['r']+1), $othercols);
	}
	
	/* Renvoie le parent du nœud '$node'. */
	public function nstAncestor($node, $othercols=null)
	{
		return $this->nstGetNodeWhere(
						$this->LeftValName.'<'.($node['l'])
				 .' AND '.$this->RightValName.'>'.($node['r'])
				 .' ORDER BY '.$this->RightValName
				, $othercols);
	}
	
	/****************************************************************************************************************/
	/*								Requêtes d'arbres (UPDATE)														/
	/**************************************************************************************************************/
	/* Les fonctions suivantes 
	/* ******************************************************************* */
	
	
	public function nstUpdateWhere($whereclause, $updatecols)
	{
		if(empty($updatecols))
			return false;
		
		$sql='UPDATE '.$this->TableName.' SET 
		'.$updatecols.' 
		WHERE '.$whereclause;
		$res = mysql_query($sql);
		
		if(!$res) {$this->_prtError();}
		else
			return true;
		
		return false;
	}
	
	public function nstUpdateNode($node, $updatecols)
	{
		return $this->nstUpdateWhere($this->LeftValName.'='.($node['l']), $updatecols);
	}
		
	public function nstUpdateSubtree($node, $updatecols)
	{
		return $this->nstUpdateWhere($this->LeftValName.'>='.($node['l']).' AND '.$this->RightValName.'<='.($node['r']), $updatecols);
	}
	
	/****************************************************************************************************************/
	/*								Fonctions d'arbres de validité													/
	/**************************************************************************************************************/
	/* 	Les fonctions suivantes retournent une valeur booléenne
	/* ******************************************************************* */

	/* Vérifie si le noeud est valide (L-value < R-value), (Aucune base de données n'est necessaire)*/
	public function nstValidNode($node)
	{
		return ($node['l'] < $node['r']);
	}
	
	/* Vérifie si le noeud a un parent*/
	public function nstHasAncestor($node)
	{
		return $this->nstValidNode($this->nstAncestor($node));
	}
	
	/* Vérifie si le noeud a un frère précèdent*/
	public function nstHasPrevSibling($node)
	{
		return $this->nstValidNode($this->nstPrevSibling($node));
	}
	
	/* Vérifie si le noeud a un frère suivant*/
	public function nstHasNextSibling($node)
	{
		return $this->nstValidNode($this->nstNextSibling($node));
	}
	
	/* Vérifie si le noeud a un ou plusieurs éléments*/
	public function nstHasChildren($node)
	{
		return (($node['r']-$node['l'])>1);
	}
	
	/* Vérifie si le noeud est la racine*/
	public function nstIsRoot($node)
	{
		return ($node['l']==1);
	}
	
	/* Vérifie si le noeud est une feuille*/
	public function nstIsLeaf($node)
	{
		return (($node['r']-$node['l'])==1);
	}
	
	/* Vérifie si 'node1' est un élément directe ou dans le sous-arbre de 'node2' */
	public function nstIsChild ($node1, $node2)
	{
		return (($node1['l']>$node2['l']) and ($node1['r']<$node2['r']));
	}
	
	/* Vérifie si 'node1' est un élément directe ou dans le sous-arbre de 'node2' ou egal à 'node2' */
	public function nstIsChildOrEqual ($node1, $node2)
	{
		return (($node1['l']>=$node2['l']) and ($node1['r']<=$node2['r']));
	}
	
	/* Vérifie si 'node1' est égal à 'node2' */
	public function nstEqual ($node1, $node2)
	{
		return (($node1['l']==$node2['l']) and ($node1['r']==$node2['r']));
	}


	/****************************************************************************************************************/
	/*								Fonctions d'arbres																/
	/**************************************************************************************************************/
	/* 	Les fonctions suivantes retournent une valeur entière
	/* ******************************************************************* */
	
	/* Retourne le nombre d'élément du nœud '$node' */
	public function nstNbChildren($node)
	{
		return (($node['r']-$node['l']-1)/2);
	}

	/* retourne le niveau du nœud '$node', soit le nombre de parent. (niveau de la racine = 0)*/
	public function nstLevel($node)
	{
		$sql='SELECT 
			COUNT(*) AS level 
			FROM '.$this->TableName.' 
			WHERE '.$this->LeftValName.'<'.$node['l'].' AND '.$this->RightValName.'>'.$node['r'];
		$res = mysql_query($sql);
		if(!$res) {$this->_prtError();}
		
		if($row = mysql_fetch_array($res))
		{
			return $row['level'];
		}
		else
		{
			return 0;
		}
	}

	/****************************************************************************************************************/
	/*								Arbre Walks																		/
	/**************************************************************************************************************/

	/* initializes preorder walk and returns a walk handle */
	public function nstWalkPreorder($node)
	{
		$sql='SELECT * 
			FROM '.$this->TableName.' 
			WHERE '.$this->LeftValName.'>='.$node['l'].' AND '.$this->RightValName.'<='.$node['r'].' 
			ORDER BY '.$this->LeftValName;
		$res = mysql_query($sql);
		if(!$res) {$this->_prtError();}

		return array('recset'=>$res,
					 'prevl'=>$node['l'], 'prevr'=>$node['r'], // needed to efficiently calculate the level
					 'level'=>-2 );
	}

	public function nstWalkNext(&$walkhand)
	{
		if($row = mysql_fetch_array($walkhand['recset'], MYSQL_ASSOC))
		{
			// calcul du niveau
			$walkhand['level']+= $walkhand['prevl'] - $row[$this->LeftValName] +2;
			// stock le nœud courant
			$walkhand['prevl'] = $row[$this->LeftValName];
			$walkhand['prevr'] = $row[$this->RightValName];
			$walkhand['row']   = $row;
			return array('l'=>$row[$this->LeftValName], 'r'=>$row[$this->RightValName]);
		}
		else	{return FALSE;}
	}

	public function nstWalkAttribute($walkhand, $attribute)
	{
		return $walkhand['row'][$attribute];
	}

	public function nstWalkCurrent($walkhand)
	{
		return array('l'=>$walkhand['prevl'], 'r'=>$walkhand['prevr']);
	}
	
	public function nstWalkLevel($walkhand)
	{
		return $walkhand['level'];
	}



	/****************************************************************************************************************/
	/*								Outils d'affichages																/
	/**************************************************************************************************************/

	/* Retourne l'attribut du noeud spécifié */
	public function nstNodeAttribute($node, $attribute)
	{
		$sql='SELECT '.$attribute.' 
			FROM '.$this->TableName.' 
			WHERE '.$this->LeftValName.'='.$node['l'];
		$res = mysql_query($sql);
		if(!$res) {$this->_prtError();}
		if($row = mysql_fetch_array ($res))
		{
			return $row[$attribute];
		}
		else
		{
			return '';
		}
	}

	/*  */
	public function nstPrintSubtree($node, $attributes)
	{
		$wlk = $this->nstWalkPreorder($node);
		while ($curr = $this->nstWalkNext($wlk))
		{
			// Affiche l'indentation
			print(str_repeat('&nbsp;', $this->nstWalkLevel($wlk)*4));
			// Affiche les attributs
			$att = reset($attributes);
			while($att)
			{
				// La ligne suivante est plus efficace:  print ($att.":".nstWalkAttribute($wlk, $att));
				print ($wlk['row'][$att]);
				$att = next($attributes);
			}
			print ('<br/>');
		}
	}

	/* Imprime les attributs de l'arbre entier. */
	public function nstPrintTree($attributes)
	{ 
		$this->nstPrintSubtree($this->nstRoot(), $attributes);
	}


	/* returns a string representing the breadcrumbs from $node to $root  
	/* retourne une chaîne représentant la chapelure de $node à $root
		 Exemple: "root > a-node > another-node > current-node"

		 Contributed by Nick Luethi
	 */
	public function nstBreadcrumbsString($node, $TitleValName)
	{
		// nœuds courant
		$ret = nstNodeAttribute($node, $TitleValName);
		// traiter les nœuds ancêtres
		while($this->nstAncestor($node) != array('l'=>0,'r'=>0))
		{
			$ret = nstNodeAttribute($this->nstAncestor($node), $TitleValName).' &gt; '.$ret;
			$node = $this->nstAncestor($node);
		}
		return $ret;
		//return "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;breadcrumb: <font size='1'>".$ret."</font>";
	} 

	/****************************************************************************************************************/
	/*								Fonctions Internes																/
	/**************************************************************************************************************/

	private function _prtError()
	{
		echo '<p>Error: '.mysql_errno().': '.mysql_error().'</p>';
	}

}
?>
