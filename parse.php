<?php
    ini_set('display_errors', 'stderr');

    # holds all possible types of tokens
    enum TokenType {
        case UNDEF;
        case HEADER;
        case INSTR;
        case VAR;
        case SYMB;
        case LABEL;
        case TYPE;
        case EOL;
    }

    # handles CLI arguments
    if ($argc == 2 && $argv[1] === "--help") {
        echo "    Skript nacte ze standardniho vstupu zdrojovy kod v IPPcode23,
    zkontroluje lexikalni a syntaktickou spravnost kodu a vypise
    na standardni vystup XML reprezentaci programu.\n\n";
        echo "    --help    Zobrazi tuto napovedu.\n";
        exit(0);
    } else if ($argc != 1) {
        exit(10);
    }

    # holds all possible data types
    enum DataType {
        case INT;
        case BOOL;
        case STRING;
        case NIL;
    }

    # holds all possible types of stack
    enum StackType {
        case LF;
        case TF;
        case GF;
    }
    
    # class to represent tokens from tokenizer
    class Token {
        public $type = TokenType::UNDEF;
        public $value;
        public $data_type;
        public $stack_type;

        function __construct(TokenType $type, $value) {
            $this->type = $type;
            $this->value = $value;
        }
    }
    
    # tokenizer takes in lines of code, splits them into tokens, marks them accordingly
    class Tokenizer {
        private $header = ".IPPcode23";
        private $instructions = array(
            "MOVE",
            "CREATEFRAME",
            "PUSHFRAME",
            "POPFRAME",
            "DEFVAR",
            "CALL",
            "RETURN",
            "PUSHS",
            "POPS",
            "ADD",
            "SUB",
            "MUL",
            "IDIV",
            "LT", "GT", "EQ",
            "AND", "OR", "NOT",
            "INT2CHAR",
            "STRI2INT",
            "READ",
            "WRITE",
            "CONCAT",
            "STRLEN",
            "GETCHAR",
            "SETCHAR",
            "TYPE",
            "LABEL",
            "JUMP",
            "JUMPIFEQ",
            "JUMPIFNEQ",
            "EXIT",
            "DPRINT",
            "BREAK"
        );

        # removes white space from tokens and deletes empty tokens
        private function FilterUTokens($tkn_arr) {
            $new_tkn_arr = array();
            foreach ($tkn_arr as $tkn) {
                $trmd_tkn = trim($tkn);
                if (strlen($trmd_tkn) > 0) {
                    array_push($new_tkn_arr, $trmd_tkn);
                }
            }
            return $new_tkn_arr;
        }

        # takes line of input code and splits it into unverified tokens
        function Line2UTokens($line) {
            $u_tkns = array();
            $trimd_line = trim($line);
            $space_split_tkns = explode(" ", $line);
            $tmp_arr = array();
            foreach ($space_split_tkns as $tkn) {
                if (str_contains($tkn, "#")) {      # removes comments from code
                    $hash_split = array_filter(explode("#", $tkn, 2));
                    if (count($hash_split) > 0) {
                        $hash_split[1] = "#" . $hash_split[1];
                    } else if (str_contains($tkn, "#")){
                        $hash_split = ["#"];
                    }
                    $tmp_arr = array_merge($tmp_arr, $hash_split);
                } else {
                    array_push($tmp_arr, $tkn);
                }
            }
            $space_split_tkns = $tmp_arr;
            $fltr_spc_tkns = $this->FilterUTokens($space_split_tkns);   # cleans tokens from whitespaces etc.
            foreach ($fltr_spc_tkns as $tkn) {
                $tmp_tkns = $this->FilterUTokens(explode("\t", $tkn));
                foreach ($tmp_tkns as $token) {
                    array_push($u_tkns, $token);
                }
            }
            if (count($u_tkns) > 0) {
                array_push($u_tkns, "\n");
            }
            return $u_tkns;
        }

        # marks undefined tokens with "variable", "symbol", "type", "stack" or "label"
        function MarkUndefToken($u_tkn) {
            if (str_contains($u_tkn, "@")) {
                $split_tkn = explode("@", $u_tkn, 2);
                if (count($split_tkn) > 1 && !$split_tkn[1] == "0"){
                    $split_tkn = array_filter($split_tkn);
                }
                $part_count = count($split_tkn);

                switch ($split_tkn[0]) {    # handles stack variables
                    case "LF":
                        if ($part_count != 2) {exit(23);}
                        $ret_tkn = new Token(TokenType::VAR, $split_tkn[1]);    # sets token type and value in the token object
                        $ret_tkn->stack_type = StackType::LF;   # sets stack type
                        return $ret_tkn;
                        break;
                    case "TF":
                        if ($part_count != 2) {exit(23);}
                        $ret_tkn = new Token(TokenType::VAR, $split_tkn[1]);
                        $ret_tkn->stack_type = StackType::TF;
                        return $ret_tkn;
                        break;
                    case "GF":
                        if ($part_count != 2) {exit(23);}
                        $ret_tkn = new Token(TokenType::VAR, $split_tkn[1]);
                        $ret_tkn->stack_type = StackType::GF;
                        return $ret_tkn;
                        break;

                    case "int":     # handles data types
                        if ($part_count == 1) {
                            exit(23);
                        } else {
                            $ret_tkn = new Token(TokenType::SYMB, $split_tkn[1]);
                            $ret_tkn->data_type = DataType::INT;
                            return $ret_tkn;
                        }
                        break;
                    case "string":
                        if ($part_count == 1) {
                            $ret_tkn = new Token(TokenType::SYMB, "");
                            $ret_tkn->data_type = DataType::STRING;
                            return $ret_tkn;
                        } else {
                            $ret_tkn = new Token(TokenType::SYMB, $split_tkn[1]);
                            $ret_tkn->data_type = DataType::STRING;
                            return $ret_tkn;
                        }
                        break;
                    case "bool":
                        if ($part_count == 1) {
                            exit(23);
                        } else {
                            $ret_tkn = new Token(TokenType::SYMB, strtolower($split_tkn[1]));
                            $ret_tkn->data_type = DataType::BOOL;
                            return $ret_tkn;
                        }
                        break;
                    case "nil":
                        if ($part_count == 1) {
                            exit(23);
                        } else {
                            $ret_tkn = new Token(TokenType::SYMB, $split_tkn[1]);
                            $ret_tkn->data_type = DataType::NIL;
                            return $ret_tkn;
                        }
                        break;
                    default:
                        exit(23);
                        break;
                }
            } else {
                switch ($u_tkn) {
                    case "int":
                    case "string":
                    case "bool":
                        return new Token(TokenType::TYPE, $u_tkn);
                        break;
                    default:
                        return new Token(TokenType::LABEL, $u_tkn);
                        break;
                }
            }
        }

        # takes array of unverified tokens and makes Token objects, marks them
        function VerifyTokens($u_tkns) {
            $v_tkns = array();
            foreach ($u_tkns as $tkn) {
                
                # check if token is "end of line"
                if (!strcmp($tkn, "\n")) {
                    array_push($v_tkns, new Token(TokenType::EOL, "\n"));
                    continue;
                }
                
                # check if token is an instruction
                $verified = false;
                foreach ($this->instructions as $instr) {
                    if (!strcasecmp($tkn, $instr)) {
                        if (count($v_tkns) > 0 && $v_tkns[count($v_tkns)-1]->type == TokenType::INSTR) {
                            array_push($v_tkns, $this->MarkUndefToken($tkn));
                        } else {
                            array_push($v_tkns, new Token(TokenType::INSTR, strtoupper($tkn)));
                        }
                        $verified = true;
                        break;
                    }
                }
                if ($verified) {continue;}
                
                # check if token is a hashtag
                if(!strcmp(substr($tkn, 0, 1), "#")) {
                    if (count($v_tkns) > 0) {
                        array_push($v_tkns, new Token(TokenType::EOL, "\n"));
                        break;
                    } else {
                        break;
                    }
                }

                # check if token is a header
                if(!strcasecmp($tkn, $this->header)) {
                    array_push($v_tkns, new Token(TokenType::HEADER, $this->header));
                    continue;
                }

                # if opcode is wrong, exit with code 22
                if (count($v_tkns) == 0) {
                    exit(22);
                }

                # if nothing else, token is marked
                array_push($v_tkns, $this->MarkUndefToken($tkn));
            }
            return $v_tkns;
        }
    }


    # analyzes syntax of input code and parses it into XML
    class Parser {

        public $xw;
        function __construct($xw) {
            $this->xw = $xw;
        }

        # checks if the next token is expected in the stream
        private function IsExpected($ex1, TokenType $type) {
            if (count($ex1) == 0) {return true;}
            foreach ($ex1 as $ex) {
                if ($ex == $type) {return true;}
            }
            return false;
        }
        
        private $expect1 = array(TokenType::HEADER);
        private $expect2 = array();
        private $expect3 = array();
        private $expect4 = array();
        private $instr_n = 0;
        
        
        function StartXMLDoc() {
            $this->xw->openMemory();
            $this->xw->setIndent(1);
            $this->xw->setIndentString("    ");
            $this->xw->startDocument("1.0", "UTF-8");
            $this->xw->startElement("program");
            $this->xw->writeAttribute("language", "IPPcode23");
        }

        function EndXMLDoc() {
            $this->xw->endElement();
            $this->xw->endDocument();
        }

        # takes in array of tokens, by line, checks syntax and parses it into XML
        function ParseTokens($tkns) {
            $a_tkns = array();
            $end_instr_el = false;
            $arg_n = 0;

            foreach ($tkns as $tkn) {
                switch ($tkn->type) {
                    case TokenType::EOL:    # handles end of line token
                        if (!$this->IsExpected($this->expect1, TokenType::EOL)) {exit(23);} # check if it's expected
                        if ($end_instr_el) {    # check if it's an end of line of an instruction, then closes element
                            $this->xw->endElement();
                            $end_instr_el = false;
                        }
                        $this->expect1 = array(TokenType::INSTR, TokenType::HEADER); # sets new expected tokens
                        $this->expect2 = array();
                        $this->expect3 = array();
                        $this->expect4 = array();
                        break;

                    case TokenType::INSTR:      # handles instruction token
                        if (!$this->IsExpected($this->expect1, TokenType::INSTR)) {exit(23);}
                        $end_instr_el = true;
                        $this->xw->startElement("instruction");
                        $this->xw->writeAttribute("order", strval(++$this->instr_n));
                        $this->xw->writeAttribute("opcode", $tkn->value);
                        switch ($tkn->value) {
                            case "STRLEN":
                            case "TYPE":
                            case "INT2CHAR":
                            case "MOVE":
                                $this->expect1 = array(TokenType::VAR);
                                $this->expect2 = array(TokenType::VAR, TokenType::SYMB);
                                $this->expect3 = array(TokenType::EOL);
                                $this->expect4 = array();
                                break;

                            case "CALL":
                            case "LABEL":
                            case "JUMP":
                                $this->expect1 = array(TokenType::LABEL);
                                $this->expect2 = array(TokenType::EOL);
                                $this->expect3 = array();
                                $this->expect4 = array();
                                break;

                            case "DEFVAR":
                            case "POPS":
                                $this->expect1 = array(TokenType::VAR);
                                $this->expect2 = array(TokenType::EOL);
                                $this->expect3 = array();
                                $this->expect4 = array();
                                break;
                            
                            case "POPFRAME":
                            case "PUSHFRAME":
                            case "RETURN":
                            case "BREAK":
                            case "CREATEFRAME":
                                $this->expect1 = array(TokenType::EOL);
                                $this->expect2 = array();
                                $this->expect3 = array();
                                $this->expect4 = array();
                                break;

                            case "WRITE":
                            case "EXIT":
                            case "DPRINT":
                            case "PUSHS":
                                $this->expect1 = array(TokenType::VAR, TokenType::SYMB);
                                $this->expect2 = array(TokenType::EOL);
                                $this->expect3 = array();
                                $this->expect4 = array();
                                break;

                            case "SUB":
                            case "MUL":
                            case "IDIV":
                            case "LT":
                            case "GT":
                            case "EQ":
                            case "AND":
                            case "OR":
                            case "NOT":
                            case "STRI2INT":
                            case "CONCAT":
                            case "GETCHAR":
                            case "SETCHAR":
                            case "ADD":
                                $this->expect1 = array(TokenType::VAR);
                                $this->expect2 = array(TokenType::VAR, TokenType::SYMB);
                                $this->expect3 = array(TokenType::VAR, TokenType::SYMB);
                                $this->expect4 = array(TokenType::EOL);
                                break;

                            case "READ":
                                $this->expect1 = array(TokenType::VAR);
                                $this->expect2 = array(TokenType::TYPE);
                                $this->expect3 = array(TokenType::EOL);
                                $this->expect4 = array();
                                break;

                            case "JUMPIFEQ":
                            case "JUMPIFNEQ":
                                $this->expect1 = array(TokenType::LABEL);
                                $this->expect2 = array(TokenType::VAR, TokenType::SYMB);
                                $this->expect3 = array(TokenType::VAR, TokenType::SYMB);
                                $this->expect4 = array(TokenType::EOL);
                                break;

                            default:
                                exit(23);
                                break;
                        }
                        break;

                    case TokenType::VAR:    # handles variable token
                        if (!$this->IsExpected($this->expect1, TokenType::VAR)) {exit(23);}
                        $this->xw->startElement("arg".strval(++$arg_n));
                        $this->xw->writeAttribute("type", "var");
                        switch ($tkn->stack_type) {
                            case StackType::GF:
                                $this->xw->text("GF@".$tkn->value);
                                break;
                            case StackType::LF:
                                $this->xw->text("LF@".$tkn->value);
                                break;
                            case StackType::TF:
                                $this->xw->text("TF@".$tkn->value);
                                break;
                            default:
                                break;
                        }
                        $this->xw->endElement();
                        $this->expect1 = $this->expect2;
                        $this->expect2 = $this->expect3;
                        $this->expect3 = $this->expect4;
                        $this->expect4 = array();
                        break;

                    case TokenType::SYMB:      # handles symbol token (constant)
                        if (!$this->IsExpected($this->expect1, TokenType::SYMB)) {exit(23);}
                        $this->xw->startElement("arg".strval(++$arg_n));
                        switch ($tkn->data_type) {
                            case DataType::INT:
                                $this->xw->writeAttribute("type", "int");
                                break;
                            case DataType::BOOL:
                                $this->xw->writeAttribute("type", "bool");
                                break;
                            case DataType::STRING:
                                $this->xw->writeAttribute("type", "string");
                                break;
                            case DataType::NIL:
                                $this->xw->writeAttribute("type", "nil");
                                break;
                            default:
                                break;
                            }
                        $this->xw->text($tkn->value);
                        $this->xw->endElement();
                        $this->expect1 = $this->expect2;
                        $this->expect2 = $this->expect3;
                        $this->expect3 = $this->expect4;
                        $this->expect4 = array();
                        break;

                    case TokenType::LABEL:     # handles label token
                        if (!$this->IsExpected($this->expect1, TokenType::LABEL)) {exit(23);}
                        $this->xw->startElement("arg".strval(++$arg_n));
                        $this->xw->writeAttribute("type", "label");
                        $this->xw->text($tkn->value);
                        $this->xw->endElement();
                        $this->expect1 = $this->expect2;
                        $this->expect2 = $this->expect3;
                        $this->expect3 = $this->expect4;
                        $this->expect4 = array();
                        break;

                    case TokenType::TYPE:      # handles type token
                        if (!$this->IsExpected($this->expect1, TokenType::TYPE)) {exit(23);}
                        $this->xw->startElement("arg".strval(++$arg_n));
                        $this->xw->writeAttribute("type", "type");
                        $this->xw->text($tkn->value);
                        $this->xw->endElement();
                        $this->expect1 = $this->expect2;
                        $this->expect2 = $this->expect3;
                        $this->expect3 = $this->expect4;
                        $this->expect4 = array();
                        break;

                    case TokenType::HEADER:     # handles header token
                        if (!$this->IsExpected($this->expect1, TokenType::HEADER)) {exit(23);}
                        $this->expect1 = array(TokenType::EOL);
                        $this->expect2 = array();
                        $this->expect3 = array();
                        $this->expect4 = array();
                        break;

                    default:
                        exit(23);
                        break;
                }
            }
        }
    }
    

    $tokenizer = new Tokenizer();
    $parser = new Parser(new XMLWriter());

    $lines = array();
    $tokens = array();

    while (!feof(STDIN)) { # loads in all lines from STDIN
        array_push($lines, fgets(STDIN));
    }

    $parser->StartXMLDoc();

    foreach ($lines as $line) {     # runs lines through tokenizer and parser
        $tmp_tkns = $tokenizer->VerifyTokens($tokenizer->Line2UTokens($line));
        $p_tkns = $parser->ParseTokens($tmp_tkns);
    }

    $parser->EndXMLDoc();
    echo $parser->xw->outputMemory();
    exit(0);
?>