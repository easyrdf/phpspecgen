<?php
    set_include_path(get_include_path() . PATH_SEPARATOR . '../easyrdf/lib/');
    require_once "EasyRdf.php";

    class Phpspecgen_Term extends EasyRdf_Resource
    {
        public function termLink() {
            $name = htmlspecialchars($this->localName());
            return "<a href=\"#term-$name\">$name</a>";
        }

        public function propertyRow($property, $title) {
            $values = $this->all($property);
            if (count($values) < 1)
                return '';

            $items = array();
            foreach ($values as $value) {
                if ($value instanceof Phpspecgen_Term) {
                    array_push($items, $value->termLink());
                } else if ($value instanceof EasyRdf_Resource) {
                    array_push($items, $value->htmlLink($value->shorten()));
                } else {
                    array_push($items, strval($value));
                }
            }
            return "<tr><th>$title:</th> <td>".implode(', ', $items)."</td></tr>\n";
        }        
    }

    class Phpspecgen_Class extends Phpspecgen_Term
    {
        public function termType() {
            return 'Class';
        }
    }

    class Phpspecgen_Property extends Phpspecgen_Term
    {
        public function termType() {
            return 'Property';
        }
    }

    class Phpspecgen_Vocab extends EasyRdf_Resource
    {
        public function htmlHeader() {
            $html = "<h1>".htmlspecialchars($this->label())."</h1>\n";
            $html .= "<em>".htmlspecialchars($this->get(
                array('dc:description', 'dc11:description', 'rdfs:comment')
            ))."</em>\n";
            
            
            $html .= "<dl>\n";
            $html .= "<dt>Latest Version</dt><dd>".$this->htmlLink()."</dd>\n";
            
            if ($this->get('dc:created')) {
                $html .= "<dt>Created</dt><dd>".$this->get('dc:created')."</dd>\n";
            }
            
            if ($this->get('dc:date')) {
                $html .= "<dt>Date</dt><dd>".$this->get('dc:date')."</dd>\n";
            }

            $authors = array();
            foreach($this->all('foaf:maker') as $author) {
                if ($author instanceof EasyRdf_Literal) {
                    array_push($authors, strval($author));
                } else if ($author->get('foaf:homepage')) {
                    array_push($authors, $author->get('foaf:homepage')->htmlLink( $author->label() ));
                } else {
                    array_push($authors, $author->label());
                }
            }
            $html .= "<dt>Authors</dt><dd>".implode(', ', $authors)."</dd>";
            
            $html .= "</dl>\n";
            return $html;
        }
    
        public function htmlSummaryOfTerms() {
            $html = "<h2 id=\"sec-summary\">Summary of Terms</h2>\n";
            $classCount = 0;
            $properyCount = 0;
            foreach($this->all("^rdfs:isDefinedBy") as $term) {
                if ($term instanceof Phpspecgen_Class)
                    $classCount++;
                if ($term instanceof Phpspecgen_Property)
                    $properyCount++;
            }
            $html .= "<p>This vocabulary defines";
            if ($classCount == 0) {
                $html .= " no classes";
            } else if ($classCount == 1) {
                $html .= " one class";
            } else {
                $html .= " $classCount classes";
            }
            if ($properyCount == 0) {
                $html .= " and no properties.";
            } else if ($properyCount == 1) {
                $html .= " and one property.";
            } else {
                $html .= " and $properyCount properties.";
            }
            $html .= "</p>\n";
            
            $html .= "<table>\n";
            $html .= "<tr><th>Term Name</th><th>Type</th><th>Definition</th></tr>\n";
            foreach($this->all("^rdfs:isDefinedBy") as $term) {
                if ($term instanceof Phpspecgen_Term) {
                    $html .= "<tr>";
                    $html .= "<td>".$term->termLink()."</td>";
                    $html .= "<td>".$term->termType()."</td>";
                    $html .= "<td>".$term->getLiteral(array('rdfs:comment', 'rdfs:label'))."</td>";
                    $html .= "</tr>\n";
                }
            }
            $html .= "</table>\n";
            return $html;
        }    
    
        public function htmlTerms($type, $title) {
            $html = '';
            $id = strtolower(str_replace(' ','-',$title));
            $html .= "<h2 id=\"sec-$id\">$title</h2>\n";
            foreach($this->all("^rdfs:isDefinedBy") as $term) {
                if (!$term instanceof $type)
                    continue;
            
                $name = htmlspecialchars($term->localName());
                $html .= "<h3 id=\"term-$name\">$name</h3\n";
                $html .= "<p>".htmlspecialchars($term->get('rdfs:comment'))."</p>\n";
                $html .= "<table>\n";
                $html .= "  <tr><th>URI:</th> <td>".$term->htmlLink()."</td></tr>\n";
                $html .= $term->propertyRow("rdfs:label", "Label");
                $html .= $term->propertyRow("vs:term_status", "Status");
                $html .= $term->propertyRow("^rdfs:subClassOf", "Has Subclasses");
                $html .= $term->propertyRow("rdfs:subClassOf", "Parent Class");
                $html .= $term->propertyRow("^rdfs:domain", "Has Properties");
                $html .= $term->propertyRow("rdfs:range", "Range");
                $html .= $term->propertyRow("rdfs:domain", "Domain");
                $html .= $term->propertyRow("rdfs:seeAlso", "See Also");
                $html .= "</table>\n";
                $html .= "</div>\n";
            }
            return $html;
        }
        
    }

    # Extra namespaces we use
    EasyRdf_Namespace::set('vann', 'http://purl.org/vocab/vann/');
    EasyRdf_Namespace::set('vs', 'http://www.w3.org/2003/06/sw-vocab-status/ns#');

    ## Add mappings
    EasyRdf_TypeMapper::set('owl:Ontology', 'Phpspecgen_Vocab');
    EasyRdf_TypeMapper::set('owl:Class', 'Phpspecgen_Class');
    EasyRdf_TypeMapper::set('rdfs:Class', 'Phpspecgen_Class');
    EasyRdf_TypeMapper::set('owl:Property', 'Phpspecgen_Property');
    EasyRdf_TypeMapper::set('owl:DatatypeProperty', 'Phpspecgen_Property');
    EasyRdf_TypeMapper::set('owl:ObjectProperty', 'Phpspecgen_Property');
    EasyRdf_TypeMapper::set('owl:InverseFunctionalProperty', 'Phpspecgen_Property');
    EasyRdf_TypeMapper::set('owl:FunctionalProperty', 'Phpspecgen_Property');
    EasyRdf_TypeMapper::set('rdf:Property', 'Phpspecgen_Property');
?>
<html>
<head>
  <title>phpspecgen</title>
  <link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>

<?php

    if (!empty($_REQUEST['uri'])) {

      // Parse the document
      $graph = new EasyRdf_Graph($_REQUEST['uri']);
      $graph->load($_REQUEST['uri']);
  
      // Get the first ontology in the document
      $vocab = $graph->get('owl:Ontology', '^rdf:type');
      if (!isset($vocab)) {
          print "<p>Error: No OWL ontologies defined at that URL.</p>\n";
      } else {
          // FIXME: register the preferredNamespacePrefix
      
          print $vocab->htmlHeader();
          print $vocab->htmlSummaryOfTerms();
          print $vocab->htmlTerms('Phpspecgen_Class', 'Classes');
          print $vocab->htmlTerms('Phpspecgen_Property', 'Properties');
    
          print $graph->dump();
      }

    } else {
        $examples = array(
            'FOAF' => 'http://xmlns.com/foaf/spec/',
            'DOAP' => 'http://usefulinc.com/ns/doap#',
            'Ordered List Ontology' => 'http://purl.org/ontology/olo/core#',
            'Whisky Vocabulary' => 'http://vocab.org/whisky/terms.rdf',
            'Sport Ontology' => 'http://www.bbc.co.uk/ontologies/sport/2011-02-17.rdf',
            'Music Ontology' => 'http://purl.org/ontology/mo/',
            'Programme Ontology' => 'http://www.bbc.co.uk/ontologies/programmes/2009-09-07.rdf'
        );

        print "<h1>phpspecgen</h1>\n";
        print "<form method='get' action='?'><div>";
        print "<div><label for='uri'>URI of a vocabulary (OWL or RDFS):</label>\n";
        print "<input type='text' id='uri' name='uri' size='40' />\n";
        print "<input type='submit' value='Submit' />\n";
        print "</div></form>\n";
        print "<p>Or pick an example:</p>\n";
        print "<ul>\n";
        foreach ($examples as $name => $uri) {
            print "<li><a href='".htmlspecialchars('?uri='.urlencode($uri))."'>".
                  htmlspecialchars($name)."</a></li>\n";
        }
        print "</ul>\n";
    
    }
?>
</body>
</html>
