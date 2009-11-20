<?php

class STL_Preprocessor {
  
  /**
   * @access private
   * checks availability and validity of if/elseif construct attribute(s)  
   *
   * @var String
   */
  private static $check_if_att = '
    (?:             # do not capture matches
      \s*           # any number of white spaces
      \w+           # check variable name
      \s*           # any number of white spaces
      [=!]=         # check equation type
      \s*           # any number of white spaces
      ".*?"         # check value
      (?:           # do not capture this match
        \s+         # at least one white space
        (?:and|or)  # check condition type
        \s+         # at least one white space
      )?            # make match optional
    )+              # one or more entry
  ';
  
  private static function parseContentBlocks($input) {
    return preg_replace(
      array(
        '~}\s*+{~s',
        '~}\s*+([^{}]+?)\s*+{~s'
      ), 
      array(
        '}{',
        '}<content><![CDATA[$1]]></content>{'
      ),
      $input
    );
  }
  
  private static function parseIfBlocks($input, $start = false) {
    
      $regex = '~
        {if(.*?)}              # match outmost opening tag at least with one attribute
        (
          (?:                  # do not capture this match
            (?!{/?if).         # use negative lookahead to ensure that text does not contain same nested tag 
            |                  # OR
            (?R)               # use recursion to handle nested tag
          )++
        )
        {/if}                  # match outmost closing tag
      ~six';
      
      if ($start) {
        $input = preg_replace($regex, '<scope><if><condition><![CDATA[$1]]></condition>$2</if></scope>', $input);
      }
      
      if (is_array($input)) {
          $input = '<if>
                <condition><![CDATA[' . $input[1] . ']]></condition>' 
                . $input[2] . 
               '</if>';
      }
  
      return preg_replace_callback($regex, array('self', 'parseIfBlocks'), $input);
      
  }
  
  private static function parseElseBlocks($input) {
    return preg_replace(
      array(
        '~{elseif(.*?)}~six',
        '~{else}~'
      ),
      array(
        '<elseif>
          <condition><![CDATA[$1]]></condition>
         </elseif>',
        '<else/>'
      ),
      $input
    );
  }
  
  private static function parseForeachBlocks($input, $start = false) {
    
    $regex = '~
        {foreach                   # match outmost opening tag at least with one attribute
          \s+                      # at least one white space  
        var\s*=\s*"(\w+)"          # match "var" attribute and capture its value
        \s+                        # at least one white space  
        key\s*=\s*"(\w+)"          # match "key" attribute and capture its value
        \s+                        # at least one white space
        value\s*=\s*"(\w+)"        # match "value" attribute and capture its value
        \s*                        # any number of white spaces
      }                   
        (
          (?:                      # do not capture this match
            (?!{/?foreach).        # use negative lookahead to ensure that text does not contain same nested tag 
            |                      # OR
            (?R)                   # use recursion to handle nested tag
          )++
        )
        {/foreach}                 # match outmost closing tag
    ~six';
    
    if ($start) {
        $input = preg_replace($regex, '<scope><foreach var="$1" key="$2" value="$3">$4</foreach></scope>', $input);
      }
    
    if (is_array($input)) {
          $input = '<foreach var="' . $input[1] . '" key="' . $input[2] . '" value="' . $input[3] . '">' 
                . $input[4] . 
               '</foreach>';
      }
  
      return preg_replace_callback($regex, array('self', 'parseForeachBlocks'), $input);
    
  }
  
  private static function parseModIteratorBlocks($input) {
    
    $regex = '~
        {mod:iterator}            # match outmost opening tag at least with one attribute
        (
          (?:                     # do not capture this match
            (?!{/?mod:iterator}). # use negative lookahead to ensure that text does not contain same nested tag 
            |                     # OR
            (?R)                  # use recursion to handle nested tag
          )*+
        )
        {/mod:iterator}           # match outmost closing tag
      ~six';
    
    if (is_array($input)) {
      $input = '<moditerator>' . $input[1] . '</moditerator>';
    }
    
    return preg_replace_callback($regex, array('self', 'parseModIteratorBlocks'), $input);
    
  }
  
  private static function parseModBlocks($input, $start = false) {
    
    $check_atts = '
      (?:                        # do not capture this match
          \s+                    # at least one white space
          (\w+)                  # capture attribute name 
          =                      # check equals sign
          "(.*?)"                # capture attribute value 
        )                  
      ';
    
    $regex = '~
      {mod:(\w+):(\w+)           # match outmost opening tag at least with one attribute
          (' . $check_atts . '*) # match and capture module attributes
      }                   
        (
          (?:                    # do not capture this match
            (?!{/?mod:\1:\2).    # use negative lookahead to ensure that text does not contain same nested tag 
            |                    # OR
            (?R)                 # use recursion to handle nested tag
          )*+
        )
        {/mod:\1:\2}             # match outmost closing tag
    ~six';
    
    if ($start) {
      $input = preg_replace($regex, '<scope><mod package="$1" name="$2" $3>$6</mod></scope>', $input);
    }
    
    if (is_array($input)) {      
      $input = '<mod package="' . $input[1] . '" name="' . $input[2] . '"' . $input[3] . '>' . $input[6] . '</mod>';                
    }
    
    return preg_replace_callback($regex, array('self', 'parseModBlocks'), $input);
    
  }
  
  private static function parseVarBlocks($input) {    
    return preg_replace('~{var:(.*?)}~si', '%%$1%%', $input);
  }
  
  private static function normalizeScopes($input) {
    return '<scope>' . str_replace(array('<scope>', '</scope>'), '', $input[1]) . '</scope>';
  }
  
  private static function normalize($input, $start = false) {
    
    if (is_array($input)) {
      
      $doc = new DOMDocument();
      $doc->loadXML('<template>' . $input[1] . '</template>');
      
      foreach(array('else', 'elseif') as $tag) {
        $nodes = $doc->getElementsByTagName($tag);
        for ($i = 0; $i < $nodes->length; $i++) {
          $node = $nodes->item($i);
          while($node->nextSibling && !preg_match('/^else(if)?$/', $node->nextSibling->nodeName, $regs)) {
            $node->appendChild($node->nextSibling);
          }
          if ($node->nodeName == 'else') {
            $node->parentNode->appendChild($node);
          }
        }
      }
      
      $input = $doc->saveXML($doc->getElementsByTagName('template')->item(0));
      
    } else if ($start) {
      
      $input = preg_replace(
        array(
          '~(</scope>)\s*<content><!\[CDATA\[(.*?)\]\]></content>\s*(<scope>)~six',
          '~<content><!\[CDATA\[(.*?)\]\]></content>\s*(<scope>)~six',
          '~(</scope>)\s*<content><!\[CDATA\[(.*?)\]\]></content>~six'
        ),
        array (
          '${1}${2}${3}',
          '${1}${2}',
          '${1}${2}'
        ), 
        preg_replace_callback(
          '~<scope>((?:(?!</?scope>).|(?R))+)</scope>~si',
          array('self', 'normalizeScopes'),
          $input
        )
      );
      
    }
    
    return preg_replace_callback('~<scope>(.*?)</scope>~si', array('self', 'normalize'), $input);
    
  }
  
  private static function extend($input) {
    
    $rtpl = '~
      {\s*content\s*block="%s"\s*}
        (.*?)
      {\s*/\s*content\s*}
    ~six';
    
    if (is_array($input)) {
      $tpl = array_pop($input);
      rsort($input);
      foreach ($input as $data) {
        $regex = sprintf($rtpl, '(\w+)');
        if (preg_match_all($regex, $tpl, $matches)) {
          foreach($matches[0] as $key=>$match) {
            $data = preg_replace(sprintf($rtpl, $matches[1][$key]), $match, $data);
          }
          $tpl = $data;
        }
      }
    }
    
    return preg_replace(sprintf($rtpl, '\w+'), '$1', $tpl);
    
  }
  
  public static function sanitize($input) {
    return preg_replace('~<!\[CDATA\[(.*?)\]\]>~is', '<cdata>$1</cdata>', $input);    
  }
  
  public static function desanitize($input) {
    return preg_replace('~<cdata>(.*?)</cdata>~is', '<![CDATA[$1]]>', $input);
  }
  
  public static function preprocess($input) {
    
    return self::normalize(
      self::parseElseBlocks(
        self::parseIfBlocks(
          self::parseForeachBlocks(
            self::parseModIteratorBlocks(
              self::parseModBlocks(
                self::parseContentBlocks(
                  self::parseVarBlocks(
                    self::sanitize(
                      self::extend($input)
                    )
                  )
                ),
                true
              )
            ),
            true
          ),
          true
        )
      ),
      true
    );
    
  }
  
  public static function toXML($doc, $print = false) {
    $xml = $doc->saveXML();
    if ($print) {
      header('Content-type: text/xml');
      echo $xml;
    }
    return $xml;
  }
  
}

class STL_FunctionProcessor {
  
  public static function isValidFunction($fn) {
    return function_exists($fn);
  }
  
  private static function exec($fn, $arg_str) {
    
    $args = array_map('trim', array_map('trim', explode(',', $arg_str)), array("'"));
  
    if (self::isValidFunction($fn)) {
      return call_user_func_array($fn, $args);
    }
    
    return null;
    
  }
  
  public static function process($input) {
    
    if (is_array($input)) {
      
      $reg = '~
        (?<fn>\w+?)     # match outmost function name
        (?P<sig>        # capture function signature
          \(            # match opening parenthesis
            (?P<args>   # capture function arguments
              [^()]++   # allow any character except parenthesis
              |         # OR
              (?P>sig)  # continue recursively 
            )
          \)
        )++
      ~x';
  
      $input = $input[1];
            
      while(preg_match_all($reg, $input, $matches)) {          
        $input = str_replace($matches[0], self::exec($matches['fn'][0], $matches['args'][0]), $input);    
      }
      
    }
    
    return preg_replace_callback('~%%fn:(.*?)%%~', array('self', 'process'), $input);
    
  }
  
}

class STL_Evaluator {
  
  private $context;
  
  public function __construct($context) {
    $this->context = $context;
  }
  
  public static function getFixedValue($value) {
    if (!is_string($value)) {
      return $value;
    }
    if (is_numeric($value)) {
      return (float) $value;
    } else if (preg_match('/^(null|false|true)$/i', $value, $matches)) {
      $values = array(
        'null'  => null,
        'false' => false,
        'true'  => true
      );
      return $values[strtolower($value)];
    }
    return $value;
  }
  
  private static function if_eq($val1, $val2) {
    return $val1 == $val2;
  }
  
  private static function if_neq($val1, $val2) {
    return $val1 != $val2;
  }
  
  private static function if_and($val1, $val2) {
    return $val1 && $val2;
  }
  
  private static function if_or($val1, $val2) {
    return $val1 || $val2;
  }
  
  public function if_true($expression) {
    
    $methods = array(
      '=='  => array('self', 'if_eq'),
      '!='  => array('self', 'if_neq'),
      'and' => array('self', 'if_and'),
      'or'  => array('self', 'if_or')
    );
    
    $parse_if_att = '~
      (?P<var>.*?)       # capture variable name
      \s*                # any number of white spaces
      (?P<eq>[=!]=)      # capture equation type
      \s*                # any number of white spaces
      "(?P<value>.*?)"   # capture value
      (?:                # do not capture this match
        \s+              # at least one white space
        (?P<cond>and|or) # capture condition type
        \s+              # at least one white space
      )?                 # make match optional
    ~six';
    
    $if_true = false;
    
    if (preg_match_all($parse_if_att, $expression, $regs)) {
      
      $context = $this->context;
      
      $cond = null;
      $tmp  = null;
      
      foreach($regs['var'] as $id => $var) {
      
        $var = $context->lookup($var);
        
        $tmp = call_user_func(
          $methods[$regs['eq'][$id]],
          self::getFixedValue($var),
          self::getFixedValue($regs['value'][$id])
        );
        
        if ($tmp == true && $regs['cond'][$id] == 'and') {
          
        } else if ($tmp == false && $regs['cond'][$id] == 'or') {
          
        } else if ($tmp == true && $regs['cond'][$id] == 'or') {
          $if_true = true;
          break;
        } else if (!$regs['cond'][$id]) {
          
        } else {            
          $if_true = false;
          break;
        }
        
        if ($cond == 'and') {
          $if_true &= $tmp;
        } else if ($cond == 'or') {
          $if_true |= $tmp;
        } else {
          $if_true = $tmp;
        }
        
        $cond = $regs['cond'][$id];
        
      }
      
    }
    
    return !!$if_true;
    
  }
  
  private $result = array();
  
  private static function loadModule($package, $name) {
    $clazz = 'Mod_' . ucfirst($package) . '_' . ucfirst($name);
    if (!class_exists($clazz)) {
      $path = dirname(__FILE__) . '/mod/' . $package . '/' . $name . '.mod.php';
      if (file_exists($path)) {
        include($path);
      }
    }
    if (class_exists($clazz)) {
      return $clazz;
    }
    return null;
  }
  
  public function evaluate($node, $debug = false) {
    
    static $recent_mod = array();
    
    $is_scope   = $node->nodeName == 'template';
    $is_if       = $node->nodeName == 'if';
    $is_elseif  = $node->nodeName == 'elseif';
    $is_else    = $node->nodeName == 'else';
    $is_content = $node->nodeName == 'content';
    $is_foreach = $node->nodeName == 'foreach';
    $is_module  = $node->nodeName == 'mod';
    $is_mod_it  = $node->nodeName == 'moditerator';
    
    if ($is_if || $is_elseif) {
      
      $condition = $node->getElementsByTagName('condition')->item(0);
      $sibling   = $condition->nextSibling;
      $if_true   = self::if_true($condition->nodeValue);
      
      if ($is_if) {
        if ($if_true) {        
          while ($sibling && !preg_match('~^else~', $sibling->nodeName, $matches)) {
            $this->evaluate($sibling);
            $sibling = $sibling->nextSibling;
          }
        } else {
          while ($sibling) {
            if ($sibling->nodeName == 'elseif') {
              $condition = $sibling->getElementsByTagName('condition')->item(0);
              if (self::if_true($condition->nodeValue)) {
                foreach($sibling->childNodes as $child) {
                  $this->evaluate($child);
                }
                break;
              }
            } else if ($sibling->nodeName == 'else') {
              $this->evaluate($sibling);
              break;
            }
            $sibling = $sibling->nextSibling;
          }
        }
      } else if ($if_elseif) {
        if ($if_true) {
          foreach($node->childNodes as $child) {
            $this->evaluate($child);
          }
        }
      }
            
    } else if ($is_else || $is_scope) {
      
      foreach($node->childNodes as $child) {
        $this->evaluate($child);
      }
      
    } else if ($is_content) {
      
      $content = $node->nodeValue;
      
      if (preg_match_all('~%%(.*?)%%~', $content, $matches)) {
        $content = str_replace(
          $matches[0], 
          array_map(array($this->context, 'lookup'), $matches[1]), 
          $content
        );
      }
      
      $this->result[] = $content;
      
    } else if ($is_foreach) {
      
      $var_name   = (string) $node->getAttribute('var');
      $key_name   = (string) $node->getAttribute('key');
      $value_name = (string) $node->getAttribute('value');
      
      $var = $this->context->lookup($var_name);
      
      if ($var && is_array($var)) {
        $index = 0;
        foreach($var as $key=>$value) {
          foreach($node->childNodes as $child) {
            $context = new STL_Context($this->context);
            $context->putAll(
              array(
                $key_name    => $key,
                $value_name  => $value,
                'length'    => sizeof($var),
                'index'     => $index
              )
            );
            $evaluator = new STL_Evaluator($context);
            if ($result = $evaluator->evaluate($child)) {
              $this->result[] = implode('', $result);
            }
          }
          $index++;
        }
      }
      
    } else if ($is_module) {
      
      $package = (string) $node->getAttribute('package');
      $name    = (string) $node->getAttribute('name');
      
      if ($clazz = self::loadModule($package, $name)) {
        
        $mod = new $clazz();
        
        $r = new ReflectionObject($mod);
        if ($r->implementsInterface('STL_IModuleContext') && isset($recent_mod[0])) {
          $mod->setModule($recent_mod[0]);
        }
        
        foreach($node->attributes as $attribute) {
          $mod->setAttribute($attribute->name, $this->context->getFromString($attribute->value));
        }
        
        $mod->init();
        $recent_mod[0] = &$mod;
        
        $context = new STL_Context($this->context);
        $context->putAll($mod->getContextData());
        
        foreach($node->childNodes as $child) {
          if ($child->nodeName != 'moditerator') {
            $evaluator = new STL_Evaluator($context);
            if ($result = $evaluator->evaluate($child)) {
              $this->result[] = implode('', $result);
            }
          } else {
            if ($mod->isIterable()) {
              while($mod->hasNext()) {                
                $modContext = new STL_Context($context);
                $modContext->putAll($mod->next());
                foreach($child->childNodes as $grandChild) {
                  $modEvaluator = new STL_Evaluator($modContext);
                  if ($result = $modEvaluator->evaluate($grandChild)) {
                    $this->result[] = implode('', $result);
                  } 
                }
              }
            }
          }
        }
        
      }
      
    } else if ($is_mod_it && $recent_mod[0] && $recent_mod[0]->isIterable()) {
      $mod = $recent_mod[0];
      while($mod->hasNext()) {                
        $modContext = new STL_Context($this->context);
        $modContext->putAll($mod->next());
        foreach($node->childNodes as $grandChild) {
          $modEvaluator = new STL_Evaluator($modContext);
          if ($result = $modEvaluator->evaluate($grandChild)) {
            $this->result[] = implode('', $result);
          } 
        }
      }
    }
    
    return $this->result;
    
  }
  
  public function write() {
    echo implode('', $this->result);
  }
  
  public function __destruct() {
    $this->context = null;
    $this->result  = null;
  }
  
}

class STL_GlobalContext {
  /**
   * @var STL_Context
   */
  private static $context;
  
  public static function put($key, $value) {
    self::getInstance()->put($key, $value);
  }
  
  public static function lookup($var) {
    return self::getInstance()->lookup($var, false);
  } 
  
  public static function getInstance() {
    if (!self::$context) {
      self::$context = new STL_Context();
      self::$context->putAll(
        array(
          'post' => $_POST,
          'get'  => $_GET
        )
      );
    }
    return self::$context;
  }
  
}

class STL_Context {
  
  private $context   = array();
  private static $ns = array('env', 'get', 'post');
  
  public function __construct($context = null) {
    if ($context) {
      if (is_array($context)) {
        $this->context = array_merge($this->context, $context);
      } else {
        $this->context = array_merge($this->context, $context->getAll());
      }
    }
  }
  
  public function put($key, $value) {
    $this->context[$key] = $value;
    return $this;
  }
  
  public function putAll($map) {
    $this->context = array_merge($this->context, $map);
    return $this;
  }
  
  public function getAll() {
    return $this->context;
  }
  
  private static function isNS($var) {
    $var = strtolower($var);
    if (in_array($var, self::$ns)) {
      switch($var) {
        case 'get':
          return $_GET;
        case 'post':
          return $_POST;
        case 'env':
          return array();        
      }
    }
    return null;
  }
  
  private static function codeCallback($input) {
    return '<pre class="ctl">' . htmlentities($input[1]) . '</pre>';
  }
  
  private static function escape($value) {
    /*if (is_string($value)) {
      return preg_replace_callback('~<pre class="ctl">(.*?)</pre>~si',
        array(
          'self',
          'codeCallback'
        ),
        stripslashes(
          html_entity_decode(
            htmlentities(
              strtr(
                $value, 
                array_flip(
                  get_html_translation_table(HTML_SPECIALCHARS)
                )
              )
            )
          )
        )
      );
    }*/
    return $value;
  }
  
  private static function getVar($context, $var, $index = null) {
    
    if (is_array($context) && isset($context[$var])) {
      
      $context = $context[$var];
      if (is_numeric($index) && isset($context[$index])) {
        $context = $context[$index];
      }
      
      return $context;
      
    } else if (is_object($context)) {
      
      $ucfirst = $lcfirst = $var;
      strtoupper($ucfirst{0});
      strtolower($lcfirst{0});
      
      $getters = array(
        'get' . $ucfirst,
        'get' . $lcfirst,
        'is'  . $var
      );
      
      try {
        
        if (isset($context->{$var})) {
          $value = $context->{$var};
          if (is_array($value) && is_numeric($index) && isset($value[$index])) {
            $value = $value[$index];
          }
          return $value;
        }
        
        throw new ReflectionException();
        
      } catch(ReflectionException $e) {
      
        try {
          
          $prop = new ReflectionProperty($context, $var);
          
          if ($prop->isPublic()) {
            $context = $prop->getValue($context);
          } else {
            throw new ReflectionException();
          }
          
        } catch(ReflectionException $e) {
          
          $value = null;
          
          foreach($getters as $getter) {
            
            try {
              
              $meth = new ReflectionMethod($context, $getter);
              
              if ($meth->isPublic()) {
                $value = $meth->invoke($context);
                break;
              }
              
            } catch (ReflectionException $e) {
              $value = null;
            }
            
          }
          
        }
        
      }
      
      if (is_array($value) && is_numeric($index) && isset($value[$index])) {
        $value = $value[$index];
      }
      
      return preg_replace('~\\\~', '', $value);
      
    }
    
    return null;
    
  }
  
  public function lookup($var, $gc = true) {
    
    $regex = '~
      (?:
        (?<var>\w+)       # match variable name
        \s*               # any number of white spaces
        (?:               # do not capture this match
          \[              # match opening square bracket
            \s*           # any number of white spaces
            (?<index>\d+) # match and capture numeric index of array
            \s*           # any number of white spaces
          \]              # match closing square bracket
        )?                # make this match optionsl
        \s*               # any number of white spaces
        (?:\.)?           # optionally match trailing dot
      ) 
    ~six';
    
    if (preg_match_all($regex, $var, $matches)) {

      $context = null;
      
      if (!$context = self::isNS($matches['var'][0])) {
        $context = &$this->context;
      }
      
      $result = $this->getVar(
        $context, 
        array_shift($matches['var']),
        array_shift($matches['index'])
      );
      
      if (sizeof($matches['var'])) { 
        while($result && sizeof($matches['var'])) {
          $result = $this->getVar(
            $result, 
            array_shift($matches['var']),
            array_shift($matches['index'])
          );
        }
      }
      
      if (!$result && $gc) {
         return STL_GlobalContext::lookup($var);
      }
      
      return self::escape($result);
      
    }
    
    return null;
    
  }
  
  private function getFromStringCallback($input) {
    return $this->lookup($input[1]);
  }
  
  public function getFromString($input) {
    return preg_replace_callback(
      '~%%(.*?)%%~',
      array($this, 'getFromStringCallback'),
      $input
    );
  }
  
  public function __destruct() {
    $this->context = null;
  }
  
}

class STL_Template {
  
  private $tpl = array();
  private $context;
  private $evaluator;
  
  public function __construct($tpl) {    
    $this->tpl[]   = $tpl;
    $this->context = new STL_Context();    
  }
  
  public function put($key, $value) {
    $this->context->put($key, $value);
    return $this->context;
  }
  
  public function extend($tpl) {
    array_unshift($this->tpl, file_get_contents($tpl));
    return $this;
  }
  
  private function evaluate($input) {
    
    if (is_array($input)) {
      $doc = new DOMDocument();
      $doc->loadXML($input[0]);
      $evaluator = new STL_Evaluator($this->context);
      $input = implode('', $evaluator->evaluate($doc->documentElement));
    }
    
    return preg_replace_callback('~<template>.*?</template>~si', array($this, 'evaluate'),  $input);
    
  }

  public function process() {
    
    '<content><![CDATA[div.block-3 div.block-header h2]]></content>';
    
    return preg_replace(
      '~<content><!\[CDATA\[(.*?)\]\]></content>~',
      '$1',
      STL_Preprocessor::desanitize(
        $this->context->getFromString(
          $this->evaluate(
            STL_Preprocessor::preprocess($this->tpl)
          )
        )
      )
    );
  
  }
  
}

interface STL_IModule {
  
}

abstract class STL_AbstractModule implements STL_IModule, STL_Type {
  
  protected $iterable = false;
  private $attributes = array();
  protected $allowedAttributes = array(
    'limit',
    'delimiter'
  );
  
  public function __construct($iterable) {
    $this->iterable = $iterable;
  }
  
  private function isAllowedAttribute($name) {
    return in_array($name, $this->allowedAttributes);
  }
  
  protected function registerAttribute($name) {
    $this->allowedAttributes[] = $name;
  }
  
  protected function registerAttributes($map) {
    $this->allowedAttributes = array_merge($this->allowedAttributes, $map);
  }
  
  public function setAttribute($name, $value) {
    if ($this->isAllowedAttribute($name)) {
      $this->attributes[$name] = $value;
    }
    return $this;
  }
  
  public function addAttributes($map) {
    $this->attributes = array_merge($this->attributes, array_filter($map, array($this, 'isAllowedAttribute')));
  }
  
  public function getAttribute($name, $defaultValue = null, $type = null) {
    $value = null;
    if (isset($this->attributes[$name])) {
      $value = $this->attributes[$name];
    }
    if (!$value) {
      $value = $defaultValue;
    }
    if ($type) {
      settype($value, $type);
    }
    return $value;
  }
  
  public function isIterable() {
    return $this->iterable;
  }
  
  public abstract function getContextData();
  public abstract function init();
  
}

interface STL_ContextDataIterator {
  public function hasNext();
  public function next();
}

interface STL_IModuleContext {
  public function setModule($module);  
}

interface STL_IPagination {
  public function getLimit();
  public function getRecordCount();
  public function getCurrentPage();
  public function getAdjacentCount();
}

interface STL_Type {
  const STR = 'string';
  const INT = 'integer';
  const FLOAT = 'float';
  const BOOLEAN = 'boolean';
}

?>