<?php
//version 0.8c - adrian


class PHPJasperXML {
    
    
    /**
     * is the used parser class object
     * @var jasperParser 
     */
    private $_parser = null;

    /** 
     * general settings 
     * 
     * 'database' => object
     * 'outputType => object
     * 
     * if these are not set the will be created. This is also set but __construtor 
     * for backwards compatablity
     * 
     * @var mixed[] 
     */
    private $_options = array();
    
    /**
     * The JasperDatabase object
     * 
     * @var type 
     */
    private $_database = null;
    
    private $adjust=1.2;
    public $version=0.9;
   
    private $pdflib;
    private $lang;
    private $previousarraydata;
    public $debugsql=false;

    private $group_name;
    
   
    public $newPageGroup = false;
   
    
    
     /**
     * below or all vars that where used but never decalared
     * @var type 
     */
    private $gnam = null;
    private $arraysubdataset  = null;
    private $arraytitle = null;
    private $curgroup=0;
    private $groupno=0;
    private $footershowed=true;
    private $titleheight=0;
    private $arrayPageSetting = null;
    private $arraystyle = null;
    private $pointer = null;
    private $arrayband = null;
    private $arrayfield = null;
    private $arraygroup = null;
    private $arraygrouphead = null;
    private $arraygroupfoot = null;
    private $arraybackground = null;
    private $arraypageHeader = null;
    private $arraycolumnHeader = null;
    private $arraydetail = null;
    private $arraycolumnFooter = null;
    private $arraypageFooter = null;
    private $arraysummary = null;
    public $arraysqltable = null;  
    private $angle = null;
    private $arrayVariable = null;
    private $cndriver = null;
    private $hideheader = null;
    private $group_pointer = null;
    /**  defines the actual class for TCPDF
    *    $pdflib only defines that it is pdf
       */
	private $pdflibClass = 'TCPDF';
    /** jasperReport/filterExpression string */
	private $filterExpression;
    /** the root path so that we can find images */
	public $report_path = '';
	private $report_count=1;		//### New declaration (variable exists in original too)
	private $group_count = array(); //### New declaration
        
    /**
     *
     * @todo need to create JasperTDCPDF object if is set $pdflib and add it to $_options
     * 
     * @param type $lang
     * @param type $pdflib
     * @param type $pdflibClass 
     */
    public function PHPJasperXML($lang="en",$pdflib="TCPDF",$pdflibClass = "TCPDF") {
        $this->lang=$lang;
        error_reporting(E_ALL  & ~E_NOTICE & ~E_WARNING);
        $this->pdflib=$pdflib;
            $this->pdflibClass=$pdflibClass;
    }
    
    function __get($name)
    {
    die($name . ' - Never declared');    
    }
  

    public function xml_dismantle($xml) {	
        $this->page_setting($xml);
        foreach ($xml as $k=>$out) {
            switch($k) {
			    
			   /**  mirrors  filterExpression from the jrxml files */
				case "filterExpression" :
			        $this->filterExpression_handler($out);
                    break;
        	
                case "parameter":
                    $this->parameter_handler($out);
                    break;
                case "queryString":
                    $this->queryString_handler($out);
                    break;
                case "field":
                    $this->field_handler($out);
                    break;
                case "variable":
                    $this->variable_handler($out);
                    break;
                case "group":
                    $this->group_handler($out);
                    break;
                case "subDataset":
                       $this->subDataset_handler($out);
                    break;
                case "background":
                    $this->pointer=&$this->arraybackground;
                    $this->pointer[]=array("height"=>$out->band["height"],"splitType"=>$out->band["splitType"]);
                    foreach ($out as $bg) {
                        $this->default_handler($bg);

                    }
                    break;
                default:
                    foreach ($out as $object) {

                        eval("\$this->pointer=&"."\$this->array$k".";");
                        $this->arrayband[]=array("name"=>$k);
                        if($k=='detail')
                        $this->detailbandheight=$object["height"]+0;
                        elseif($k=='pageHeader')
                        $this->headerbandheight=$object["height"]+0;
                        elseif($k=='pageFooter')
                        $this->footerbandheight=$object["height"]+0;
                        elseif($k=='lastPageFooter')
                        $this->lastfooterbandheight=$object["height"]+0;
                        elseif($k=='summary')
                        $this->summarybandheight=$object["height"]+0;
                        
                        $this->pointer[]=array("type"=>"band","height"=>$object["height"],"splitType"=>$object["splitType"],"y_axis"=>$this->y_axis);
                        $this->default_handler($object);
                    }
                    $this->y_axis=$this->y_axis+$out->band["height"];	//after handle , then adjust y axis

                    break;

            }

        }
    }
public $_fontmap  =array();
function getFontMap($family)
 {
  
  $sub = $this->_fontmap[strtolower($family)];
  if (!empty($sub))
    return $sub;

  return $family;
 
 }
    public function subDataset_handler($data){
    $this->subdataset[$data['name'].'']= $data->queryString;

    }
//read level 0,Jasperreport page setting
    public function page_setting($xml_path) {
        $this->arrayPageSetting["orientation"]="P";
        $this->arrayPageSetting["name"]=$xml_path["name"];
        $this->arrayPageSetting["language"]=$xml_path["language"] ;
        
        // *1 to force cast to int or float 
        $this->arrayPageSetting["pageWidth"]=$xml_path["pageWidth"] * 1;
        $this->arrayPageSetting["pageHeight"]=$xml_path["pageHeight"]* 1;
        if(isset($xml_path["orientation"])) {
            $this->arrayPageSetting["orientation"]=substr($xml_path["orientation"],0,1);
        }
        $this->arrayPageSetting["columnWidth"]=$xml_path["columnWidth"]* 1;
        $this->arrayPageSetting["leftMargin"]=$xml_path["leftMargin"]* 1;
        $this->arrayPageSetting["rightMargin"]=$xml_path["rightMargin"]* 1;
        $this->arrayPageSetting["topMargin"]=$xml_path["topMargin"]* 1;
        $this->y_axis=$xml_path["topMargin"]* 1;
        $this->arrayPageSetting["bottomMargin"]=$xml_path["bottomMargin"]* 1;
    }

    public function parameter_handler($xml_path) {
        //    $defaultValueExpression=str_replace('"','',$xml_path->defaultValueExpression);
      // if($defaultValueExpression!='')
      //  $this->arrayParameter[$xml_path["name"].'']=$defaultValueExpression;
      // else
        $this->arrayParameter[$xml_path["name"].''];        

    }
    public function filterExpression_handler($xml_path) {
 
	  
        $this->filterExpression = $xml_path;      
		
    }   	


    public function queryString_handler($xml_path) {
        $this->sql =$xml_path;
        if(isset($this->arrayParameter)) {
            foreach($this->arrayParameter as  $v => $a) {
                $this->sql = str_replace('$P{'.$v.'}', $a, $this->sql);
            }
        }
    }

    public function field_handler($xml_path) {
        $this->arrayfield[]=$xml_path["name"];
    }

    public function variable_handler($xml_path) {

        $this->arrayVariable["$xml_path[name]"]=array("calculation"=>$xml_path["calculation"],"target"=>substr($xml_path->variableExpression,3,-1),"class"=>$xml_path["class"] , "resetType"=>$xml_path["resetType"]);

    }

    public function group_handler($xml_path) {

//        $this->arraygroup=$xml_path;


        if($xml_path["isStartNewPage"]=="true")
            $this->newPageGroup=true;
        else
            $this->newPageGroup="";

        foreach($xml_path as $tag=>$out) {
            switch ($tag) {
                case "groupHeader":
                    $this->pointer=&$this->arraygroup[$xml_path["name"]]["groupHeader"];
                    $this->pointer=&$this->arraygrouphead;
                    $this->arraygroupheadheight=$out->band["height"];
                    $this->arrayband[]=array("name"=>"group", "gname"=>$xml_path["name"],"isStartNewPage"=>$xml_path["isStartNewPage"],"groupExpression"=>substr($xml_path->groupExpression,3,-1));
                    $this->pointer[]=array("type"=>"band","height"=>$out->band["height"]+0,"y_axis"=>"","groupExpression"=>substr($xml_path->groupExpression,3,-1));
//### Modification for group count
					$gnam=$xml_path["name"];				
					$this->gnam=$xml_path["name"];
					$this->group_count["$gnam"]=1; // Count rows of groups, we're on the first row of the group.
//### End of modification
                    foreach($out as $band) {
                        $this->default_handler($band);

                    }

                    $this->y_axis=$this->y_axis+$out->band["height"];		//after handle , then adjust y axis
                    break;
                case "groupFooter":

                    $this->pointer=&$this->arraygroup[$xml_path["name"]]["groupFooter"];
                    $this->pointer=&$this->arraygroupfoot;
                    $this->arraygroupfootheight=$out->band["height"];
                    $this->pointer[]=array("type"=>"band","height"=>$out->band["height"]+0,"y_axis"=>"","groupExpression"=>substr($xml_path->groupExpression,3,-1));
                    foreach($out as $b=>$band) {
                        $this->default_handler($band);

                    }
                    break;
                default:

                    break;
            }

        }
    }

  public function default_handler($xml_path) {
        foreach($xml_path as $k=>$out) {

            switch($k) {
                case "staticText":
                    $this->element_staticText($out);
                    break;
                case "image":
                    $this->element_image($out);
                    break;
                case "line":
                    $this->element_line($out);
                    break;
                case "rectangle":
                    $this->element_rectangle($out);
                    break;
            case "ellipse":
                    $this->element_ellipse($out);
                    break;
	                case "textField":
                    $this->element_textField($out);
                    break;
//                case "stackedBarChart":
//                    $this->element_barChart($out,'StackedBarChart');
//                    break;
//                case "barChart":
//                    $this->element_barChart($out,'BarChart');
//                    break;
//                case "pieChart":
//                    $this->element_pieChart($out);
//                    break;
//                case "pie3DChart":
//                    $this->element_pie3DChart($out);
//                    break;
//                case "lineChart":
//                    $this->element_lineChart($out);
//                    break;
//                case "stackedAreaChart":
//                    $this->element_areaChart($out,'stackedAreaChart');
//                    break;
                    case "stackedBarChart":
                    $this->element_Chart($out,'stackedBarChart');
                    break;
                case "barChart":
                    $this->element_Chart($out,'barChart');
                    break;
                case "pieChart":
                    $this->element_Chart($out,'pieChart');
                    break;
                case "pie3DChart":
                    $this->element_pie3DChart($out,'pie3DChart');
                    break;
                case "lineChart":
                    $this->element_Chart($out,'lineChart');
                    break;
                case "stackedAreaChart":
                    $this->element_Chart($out,'stackedAreaChart');
                    break;
                case "subreport":
                    $this->element_subReport($out);
                    break;
                default:
                    break;
            }
        };		
    }

    public function element_staticText($data) {
        $align="L";
        $fill=0;
        $border=0;
        $fontsize=10;
        $font="helvetica";
        $fontstyle="";
        $textcolor = array("r"=>0,"g"=>0,"b"=>0);
        $fillcolor = array("r"=>255,"g"=>255,"b"=>255);
        $txt="";
        $rotation="";
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        $height=$data->reportElement["height"];
        $stretchoverflow="true";
        $printoverflow="false";

/** allow forground color "forecolor" */ 
        if(isset($data->reportElement["forecolor"])) {
            $textcolor = array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));
        }
        if(isset($data->reportElement["backcolor"])) {
            $fillcolor = array("r"=>hexdec(substr($data->reportElement["backcolor"],1,2)),"g"=>hexdec(substr($data->reportElement["backcolor"],3,2)),"b"=>hexdec(substr($data->reportElement["backcolor"],5,2)));
        }
        if($data->reportElement["mode"]=="Opaque") {
            $fill=1;
        }
        if(isset($data["isStretchWithOverflow"])&&$data["isStretchWithOverflow"]=="true") {
            $stretchoverflow="true";
        }
        if(isset($data->reportElement["isPrintWhenDetailOverflows"])&&$data->reportElement["isPrintWhenDetailOverflows"]=="true") {
            $printoverflow="true";
            $stretchoverflow="false";
        }
        if((isset($data->box))&&($data->box->pen["lineWidth"]>0)) {
            $border=1;
            if(isset($data->box->pen["lineColor"])) {
                $drawcolor=array("r"=>hexdec(substr($data->box->pen["lineColor"],1,2)),"g"=>hexdec(substr($data->box->pen["lineColor"],3,2)),"b"=>hexdec(substr($data->box->pen["lineColor"],5,2)));
            }
        }
        if(isset($data->textElement["textAlignment"])) {
            $align=$this->get_first_value($data->textElement["textAlignment"]);
        }
        if(isset($data->textElement["rotation"])) {
            $rotation=$data->textElement["rotation"];
        }
        if(isset($data->textElement->font["fontName"])) {
            $font=$data->textElement->font["fontName"];
        }

        if(isset($data->textElement->font["pdfFontName"])) {
            $font=$data->textElement->font["pdfFontName"];
        }
        if(isset($data->textElement->font["size"])) {
            $fontsize=$data->textElement->font["size"];
        }
        if(isset($data->textElement->font["isBold"])&&$data->textElement->font["isBold"]=="true") {
            $fontstyle=$fontstyle."B";
        }
        if(isset($data->textElement->font["isItalic"])&&$data->textElement->font["isItalic"]=="true") {
            $fontstyle=$fontstyle."I";
        }
        if(isset($data->textElement->font["isUnderline"])&&$data->textElement->font["isUnderline"]=="true") {
            $fontstyle=$fontstyle."U";
        }
        if(isset($data->reportElement["key"])) {
            $height=$fontsize*$this->adjust;
        }
        $this->pointer[]=array("type"=>"SetXY","x"=>$data->reportElement["x"],"y"=>$data->reportElement["y"],"hidden_type"=>"SetXY");
        $this->pointer[]=array("type"=>"SetTextColor","r"=>$textcolor["r"],"g"=>$textcolor["g"],"b"=>$textcolor["b"],"hidden_type"=>"textcolor");
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor");
        $this->pointer[]=array("type"=>"SetFillColor","r"=>$fillcolor["r"],"g"=>$fillcolor["g"],"b"=>$fillcolor["b"],"hidden_type"=>"fillcolor");
        $this->pointer[]=array("type"=>"SetFont","font"=>$font,"fontstyle"=>$fontstyle,"fontsize"=>$fontsize,"hidden_type"=>"font");
        //"height"=>$data->reportElement["height"]
//### UTF-8 characters, a must for me.	
		$txtEnc=($data->text);
              
/** add printWhenExpression */
		$this->pointer[]=array("type"=>"MultiCell",
		                "printWhenExpression"=>$data->reportElement->printWhenExpression,
						"width"=>$data->reportElement["width"],"height"=>$height,"txt"=>$txtEnc,"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"statictext","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"rotation"=>$rotation);
//### End of modification, below is the original line		
//        $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>$data->text,"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"statictext","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"rotation"=>$rotation);

    }

    public function element_image($data) {
        $imagepath=$data->imageExpression;
        //$imagepath= substr($data->imageExpression, 1, -1);
        //$imagetype= substr($imagepath,-3);
		
  // $imagepath=$this->analyse_expression($imagepath);
     
        switch($data[scaleImage]) {
            case "FillFrame":
/** add hAlign */
                $this->pointer[]=array("type"=>"Image","path"=>$imagepath,"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0,"height"=>$data->reportElement["height"]+0,"imgtype"=>$imagetype,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"hidden_type"=>"image",'hAlign'=>$data['hAlign'] );
                break;
            default:
                $this->pointer[]=array("type"=>"Image","path"=>$imagepath,"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0,"height"=>$data->reportElement["height"]+0,"imgtype"=>$imagetype,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"hidden_type"=>"image");
                break;
        }
    }

    public function element_line($data) {	//default line width=0.567(no detect line width)
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        $hidden_type="line";
        if(isset($data->reportElement["forecolor"])) {
            $drawcolor=array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));
        }
    /** add drawing of lines */
	 if ((isset($data->graphicElement) ) and (isset($data->graphicElement->pen)))
         {
		  if(isset($data->graphicElement->pen["lineColor"])) 
              $drawcolor=array("r"=>hexdec(substr($data->graphicElement->pen["lineColor"],1,2)),"g"=>hexdec(substr($data->graphicElement->pen["lineColor"],3,2)),"b"=>hexdec(substr($data->graphicElement->pen["lineColor"],5,2)));
           if(isset($data->graphicElement->pen["lineWidth"])) 
       $this->pointer[]=array("type"=>"SetLineWidth","width"=>$data->graphicElement->pen["lineWidth"]);
               
		 
		 }

	 $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor");
        if(isset($data->reportElement[positionType])&&$data->reportElement[positionType]=="FixRelativeToBottom") {
            $hidden_type="relativebottomline";
        }
        if($data->reportElement["width"][0]+0 > $data->reportElement["height"][0]+0)	//width > height means horizontal line
        {
            $this->pointer[]=array("type"=>"Line", "x1"=>$data->reportElement["x"],"y1"=>$data->reportElement["y"],"x2"=>$data->reportElement["x"]+$data->reportElement["width"],"y2"=>$data->reportElement["y"]+$data->reportElement["height"]-1,"hidden_type"=>$hidden_type);
        }
        elseif($data->reportElement["height"][0]+0>$data->reportElement["width"][0]+0)		//vertical line
        {
            $this->pointer[]=array("type"=>"Line", "x1"=>$data->reportElement["x"],"y1"=>$data->reportElement["y"],"x2"=>$data->reportElement["x"]+$data->reportElement["width"]-1,"y2"=>$data->reportElement["y"]+$data->reportElement["height"],"hidden_type"=>$hidden_type);
        }
	     $this->pointer[]=array("type"=>"SetLineWidth","width"=>"1");
      
		$this->pointer[]=array("type"=>"SetDrawColor","r"=>0,"g"=>0,"b"=>0,"hidden_type"=>"drawcolor");
        $this->pointer[]=array("type"=>"SetFillColor","r"=>255,"g"=>255,"b"=>255,"hidden_type"=>"fillcolor");
    }

    public function element_rectangle($data) {
		
		$radius=$data['radius'];
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        
        $fillcolor=array("r"=>255,"g"=>255,"b"=>255);

        
        if(isset($data->reportElement["forecolor"])) {
            $drawcolor=array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));			
        }
        
        if(isset($data->reportElement["backcolor"]) ) {
            $fillcolor=array("r"=>hexdec(substr($data->reportElement["backcolor"],1,2)),"g"=>hexdec(substr($data->reportElement["backcolor"],3,2)),"b"=>hexdec(substr($data->reportElement["backcolor"],5,2)));			
        }


        $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor");
        $this->pointer[]=array("type"=>"SetFillColor","r"=>$fillcolor["r"],"g"=>$fillcolor["g"],"b"=>$fillcolor["b"],"hidden_type"=>"fillcolor");
		
        if($radius=='')
        $this->pointer[]=array("type"=>"Rect","x"=>$data->reportElement["x"],"y"=>$data->reportElement["y"],"width"=>$data->reportElement["width"],"height"=>$data->reportElement["height"],"hidden_type"=>"rect","drawcolor"=>$drawcolor,"fillcolor"=>$fillcolor,"mode"=>$data->reportElement["mode"]);
        else
        $this->pointer[]=array("type"=>"RoundedRect","x"=>$data->reportElement["x"],"y"=>$data->reportElement["y"],"width"=>$data->reportElement["width"],"height"=>$data->reportElement["height"],"hidden_type"=>"roundedrect","radius"=>$radius,"drawcolor"=>$drawcolor,"fillcolor"=>$fillcolor,"mode"=>$data->reportElement["mode"]);
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>0,"g"=>0,"b"=>0,"hidden_type"=>"drawcolor");
        $this->pointer[]=array("type"=>"SetFillColor","r"=>255,"g"=>255,"b"=>255,"hidden_type"=>"fillcolor");
    }

  public function element_ellipse($data) {
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        $fillcolor=array("r"=>255,"g"=>255,"b"=>255);
        if(isset($data->reportElement["forecolor"])) {
            $drawcolor=array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));			
        }
        if(isset($data->reportElement["backcolor"])) {
            $fillcolor=array("r"=>hexdec(substr($data->reportElement["backcolor"],1,2)),"g"=>hexdec(substr($data->reportElement["backcolor"],3,2)),"b"=>hexdec(substr($data->reportElement["backcolor"],5,2)));			
        }
        
		//$color=array("r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"]);
        $this->pointer[]=array("type"=>"SetFillColor","r"=>$fillcolor["r"],"g"=>$fillcolor["g"],"b"=>$fillcolor["b"],"hidden_type"=>"fillcolor");
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor");
        $this->pointer[]=array("type"=>"Ellipse","x"=>$data->reportElement["x"],"y"=>$data->reportElement["y"],"width"=>$data->reportElement["width"],"height"=>$data->reportElement["height"],"hidden_type"=>"ellipse","drawcolor"=>$drawcolor,"fillcolor"=>$fillcolor);
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>0,"g"=>0,"b"=>0,"hidden_type"=>"drawcolor");
        $this->pointer[]=array("type"=>"SetFillColor","r"=>255,"g"=>255,"b"=>255,"hidden_type"=>"fillcolor");
    }
    
    public function element_textField($data) {
        $align="L";
        $fill=0;
        $border=0;
        $fontsize=10;
        $font="helvetica";
        $rotation="";
        $fontstyle="";
        $textcolor = array("r"=>0,"g"=>0,"b"=>0);
        $fillcolor = array("r"=>255,"g"=>255,"b"=>255);
        $stretchoverflow="false";
        $printoverflow="false";
        $height=$data->reportElement["height"];
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        if(isset($data->reportElement["forecolor"])) {
            $textcolor = array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));
        }
        if(isset($data->reportElement["backcolor"])) {
            $fillcolor = array("r"=>hexdec(substr($data->reportElement["backcolor"],1,2)),"g"=>hexdec(substr($data->reportElement["backcolor"],3,2)),"b"=>hexdec(substr($data->reportElement["backcolor"],5,2)));
        }
        if($data->reportElement["mode"]=="Opaque") {
            $fill=1;
        }
        if(isset($data["isStretchWithOverflow"])&&$data["isStretchWithOverflow"]=="true") {
            $stretchoverflow="true";
        }
        if(isset($data->reportElement["isPrintWhenDetailOverflows"])&&$data->reportElement["isPrintWhenDetailOverflows"]=="true") {
            $printoverflow="true";
        }
        if(isset($data->box)&&$data->box->pen["lineWidth"]>0) {
            $border=1;
            if(isset($data->box->pen["lineColor"])) {
                $drawcolor=array("r"=>hexdec(substr($data->box->pen["lineColor"],1,2)),"g"=>hexdec(substr($data->box->pen["lineColor"],3,2)),"b"=>hexdec(substr($data->box->pen["lineColor"],5,2)));
            }
        }
        if(isset($data->reportElement["key"])) {
            $height=$fontsize*$this->adjust;
        }
        if(isset($data->textElement["textAlignment"])) {
            $align=$this->get_first_value($data->textElement["textAlignment"]);
        }
             /** get verital align */
        if(isset($data->textElement["textAlignment"])) {
            $valign=$this->get_first_value($data->textElement["verticalAlignment"]);
        }
        if(isset($data->textElement["rotation"])) {
            $rotation=$data->textElement["rotation"];
        }
        if(isset($data->textElement->font["fontName"])) {
            $font=$data->textElement->font["fontName"];
        }
        if(isset($data->textElement->font["pdfFontName"])) {
            $font=$data->textElement->font["pdfFontName"];
        }
        if(isset($data->textElement->font["size"])) {
            $fontsize=$data->textElement->font["size"];
        }
        if(isset($data->textElement->font["isBold"])&&$data->textElement->font["isBold"]=="true") {
            $fontstyle=$fontstyle."B";
        }
        if(isset($data->textElement->font["isItalic"])&&$data->textElement->font["isItalic"]=="true") {
            $fontstyle=$fontstyle."I";
        }
        if(isset($data->textElement->font["isUnderline"])&&$data->textElement->font["isUnderline"]=="true") {
            $fontstyle=$fontstyle."U";
        }
        $this->pointer[]=array("type"=>"SetXY","x"=>$data->reportElement["x"],"y"=>$data->reportElement["y"],"hidden_type"=>"SetXY");
 /** todo: need to check that forecolor and backcolor work. I add it from older code without checking it */
        $this->pointer[]=array("type"=>"SetTextColor","forecolor"=>$data->reportElement["forecolor"],"r"=>$textcolor["r"],"g"=>$textcolor["g"],"b"=>$textcolor["b"],"hidden_type"=>"textcolor");
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor");
        $this->pointer[]=array("type"=>"SetFillColor","backcolor"=>$data->reportElement["backcolor"],"r"=>$fillcolor["r"],"g"=>$fillcolor["g"],"b"=>$fillcolor["b"],"hidden_type"=>"fillcolor");
        $this->pointer[]=array("type"=>"SetFont","font"=>$font,"fontstyle"=>$fontstyle,"fontsize"=>$fontsize,"hidden_type"=>"font");
         //$data->hyperlinkReferenceExpression=$this->analyse_expression($data->hyperlinkReferenceExpression);
        //if( $data->hyperlinkReferenceExpression!=''){echo "$data->hyperlinkReferenceExpression";die;}


        switch ($data->textFieldExpression) {
            case 'new java.util.Date()':
//### New: =>date("Y.m.d.",....
/** added valign  for the next 35 lines */
                $this->pointer[]=array ("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>date("Y-m-d H:i:s"),"border"=>$border,"align"=>$align,'valign'=>$valign,"fill"=>$fill,"hidden_type"=>"date","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1));
//### End of modification				
                break;
            case '"Page "+$V{PAGE_NUMBER}+" of"':

                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>'Page $this->PageNo() of',"border"=>$border,"align"=>$align,'valign'=>$valign,"fill"=>$fill,"hidden_type"=>"pageno","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"]);
                break;
            case '$V{PAGE_NUMBER}':
                if(isset($data["evaluationTime"])&&$data["evaluationTime"]=="Report") {
                    $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>'{nb}',"border"=>$border,"align"=>$align,'valign'=>$valign,"fill"=>$fill,"hidden_type"=>"pageno","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"]);
                }
                else {
                    $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>'$this->PageNo()',"border"=>$border,"align"=>$align,'valign'=>$valign,"fill"=>$fill,"hidden_type"=>"pageno","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"]);
                }
                break;
            case '" " + $V{PAGE_NUMBER}':
                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>' {nb}',"border"=>$border,"align"=>$align,'valign'=>$valign,"fill"=>$fill,"hidden_type"=>"nb","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"]);
                break;
            case '$V{REPORT_COUNT}':
//###                $this->report_count=0;	
                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>&$this->report_count,"border"=>$border,"align"=>$align,'valign'=>$valign,"fill"=>$fill,"hidden_type"=>"report_count","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"]);
                break;
            case '$V{'.$this->gnam.'_COUNT}':
//            case '$V{'.$this->arrayband[0]["gname"].'_COUNT}':
//###                $this->group_count=0;
				$gnam=$this->arrayband[0]["gname"];																
                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>&$this->group_count["$this->gnam"],"border"=>$border,"align"=>$align,'valign'=>$valign,"fill"=>$fill,"hidden_type"=>"group_count","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"]);
                break;
            default:
                $writeHTML=false;
                if($data->reportElement->property["name"]=="writeHTML")
                    $writeHTML=$data->reportElement->property["value"];
                if(isset($data->reportElement["isPrintRepeatedValues"]))
                    $isPrintRepeatedValues=$data->reportElement["isPrintRepeatedValues"];


                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>$data->textFieldExpression,
                        "border"=>$border,"align"=>$align,"fill"=>$fill,'valign'=>$valign,
                        "hidden_type"=>"field","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,
                        "printWhenExpression"=>$data->reportElement->printWhenExpression,
                        "link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],
                        "writeHTML"=>$writeHTML,"isPrintRepeatedValues"=>$isPrintRepeatedValues,"rotation"=>$rotation);
                break;
        }
    }

    public function element_subReport($data) {
//        $b=$data->subreportParameter;
                $srsearcharr=array('.jasper','"',"'",' ','$P{SUBREPORT_DIR}+');
                $srrepalcearr=array('.jrxml',"","",'',$this->arrayParameter['SUBREPORT_DIR']);

                if (strpos($data->subreportExpression,'$P{SUBREPORT_DIR}') === false){
                    $subreportExpression=str_replace($srsearcharr,$srrepalcearr,$data->subreportExpression);
                }
                else{
                    $subreportExpression=str_replace($srsearcharr,$srrepalcearr,$data->subreportExpression);
                }
                $b=array(); 
                foreach($data as $name=>$out){
                        if($name=='subreportParameter'){
                            $b[$out['name'].'']=$out->subreportParameterExpression;
                        }
                }//loop to let multiple parameter pass to subreport pass to subreport
                $this->pointer[]=array("type"=>"subreport", "x"=>$data->reportElement["x"], "y"=>$data->reportElement["y"],
                        "width"=>$data->reportElement["width"], "height"=>$data->reportElement["height"],
                        "subreportparameterarray"=>$b,"connectionExpression"=>$data->connectionExpression,
                        "subreportExpression"=>$subreportExpression,"hidden_type"=>"subreport");
    }

    public function transferDBtoArray($host,$user,$password,$db_or_dsn_name,$cndriver="mysql") {
        $this->m=0;

        $this->_database = new JasperMysql(); 
        
        if(!$this->_database->connect($host,$user,$password,$db_or_dsn_name,$cndriver))	//connect database
        {
            echo "Fail to connect database";
            exit(0);
        }
        if($this->debugsql==true) {
            echo $this->sql;
            die;
        }
   /* 
    * 
    
    this code will be fixed once mysql works 
    
        if($cndriver=="odbc") {

            $result=odbc_exec( $this->myconn,$this->sql);
            while ($row = odbc_fetch_array($result)) {
                foreach($this->arrayfield as $out) {
                    $this->arraysqltable[$this->m]["$out"]=$row["$out"];
                }
                $this->m++;
            }
        }elseif($cndriver=="psql") {


            pg_send_query($this->myconn,$this->sql);
            $result = pg_get_result($this->myconn);
            while ($row = pg_fetch_array($result, NULL, PGSQL_ASSOC)) {
                foreach($this->arrayfield as $out) {
                    $this->arraysqltable[$this->m]["$out"]=$row["$out"];
                }
                $this->m++;
            }
        }
        else
    * 
    *
    */  
    
          {
            $result =$this->_database->query($this->sql); //query from db

            while ($row = $this->_database->next($result) ) {
                foreach($this->arrayfield as $out) {
                    $this->arraysqltable[$this->m]["$out"]=$row["$out"];
                }
                $this->m++;
            }
        }
         // I dont think we need to close the database, it makes execution very slow.
       	//close connection to db

    }
   

    public function time_to_sec($time) {
        $hours = substr($time, 0, -6);
        $minutes = substr($time, -5, 2);
        $seconds = substr($time, -2);

        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    public function sec_to_time($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor($seconds % 3600 / 60);
        $seconds = $seconds % 60;

        return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
    }

    public function orivariable_calculation() {

        foreach($this->arrayVariable as $k=>$out) {
         //   echo $out['resetType']. "<br/><br/>";
            switch($out["calculation"]) {
                case "Sum":
                    $sum=0;
                    if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                        foreach($this->arraysqltable as $table) {
                            $sum=$sum+$this->time_to_sec($table["$out[target]"]);
                            //$sum=$sum+substr($table["$out[target]"],0,2)*3600+substr($table["$out[target]"],3,2)*60+substr($table["$out[target]"],6,2);
                        }
                        //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
                        //if($sum=="0:0"){$sum="00:00";}
                        $sum=$this->sec_to_time($sum);
                    }
                    else {
                        foreach($this->arraysqltable as $table) {
                            $sum=$sum+$table[$out["target"]];
                            $table[$out["target"]];
                        }
                    }

                    $this->arrayVariable[$k]["ans"]=$sum;
                    break;
                case "Average":

                    $sum=0;

                    if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                        $m=0;
                        foreach($this->arraysqltable as $table) {
                            $m++;

                            $sum=$sum+$this->time_to_sec($table["$out[target]"]);


                        }

                        $sum=$this->sec_to_time($sum/$m);
                        $this->arrayVariable[$k]["ans"]=$sum;

                    }
                    else {
                        $this->arrayVariable[$k]["ans"]=$sum;
                        $m=0;
                        foreach($this->arraysqltable as $table) {
                            $m++;
                            $sum=$sum+$table["$out[target]"];
                        }
                        $this->arrayVariable[$k]["ans"]=$sum/$m;


                    }


                    break;
                case "DistinctCount":
                    break;
                case "Lowest":

                    foreach($this->arraysqltable as $table) {
                        $lowest=$table[$out["target"]];
                        if($table[$out["target"]]<$lowest) {
                            $lowest=$table[$out["target"]];
                        }
                        $this->arrayVariable[$k]["ans"]=$lowest;
                    }
                    break;
                case "Highest":
                    $out["ans"]=0;
                    foreach($this->arraysqltable as $table) {
                        if($table[$out["target"]]>$out["ans"]) {
                            $this->arrayVariable[$k]["ans"]=$table[$out["target"]];
                        }
                    }
                    break;
//### A Count for groups, as a variable. Not tested yet, but seemed to work in print_r()
				case "Count":
					$value=$this->arrayVariable[$k]["ans"];
					if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
                       $value=0;
					$value++;
                    $this->arrayVariable[$k]["ans"]=$value;
				break;	
//### End of modification				
                default:
                    $out["target"]=0;		//other cases needed, temporary leave 0 if not suitable case
                    break;

            }
        }
    }


      public function variable_calculation($rowno) {
//   $this->variable_calculation($rownum, $this->arraysqltable[$this->global_pointer][$this->group_pointer]);
     //   print_r($this->arraysqltable);


        foreach($this->arrayVariable as $k=>$out) {
         //   echo $out['resetType']. "<br/><br/>";
            switch($out["calculation"]) {
                case "Sum":

                         $value=$this->arrayVariable[$k]["ans"];
                    if($out['resetType']==''){
                            if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                            //    foreach($this->arraysqltable as $table) {
                                    $value=$this->time_to_sec($value);

                                    $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
                                    //$sum=$sum+substr($table["$out[target]"],0,2)*3600+substr($table["$out[target]"],3,2)*60+substr($table["$out[target]"],6,2);
                               // }
                                //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
                                //if($sum=="0:0"){$sum="00:00";}
                                $value=$this->sec_to_time($value);
                            }
                            else {
                               // foreach($this->arraysqltable as $table) {
                                         $value+=$this->arraysqltable[$rowno]["$out[target]"];

                              //      $table[$out["target"]];
                             //   }
                            }
                    }// finisish resettype=''
                    else //reset type='group'
                    {if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
                             $value=0;
                      //    echo $this->global_pointer.",".$this->group_pointer.",".$this->arraysqltable[$this->global_pointer][$this->group_pointer].",".$this->arraysqltable[$this->global_pointer-1][$this->group_pointer].",".$this->arraysqltable[$rowno]["$out[target]"];
                                 if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                                      $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
                                //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
                                //if($sum=="0:0"){$sum="00:00";}
                                $value=$this->sec_to_time($value);
                            }
                            else {
                                      $value+=$this->arraysqltable[$rowno]["$out[target]"];
                            }
                    }


                    $this->arrayVariable[$k]["ans"]=$value;
              //      echo ",$value<br/>";
                    break;
                case "Average":

                    $sum=0;

                    if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                        $m=0;
                        //$value=$this->arrayVariable[$k]["ans"];
                        //$value=$this->time_to_sec($value);
                        //$value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);

                        foreach($this->arraysqltable as $table) {
                            $m++;

                             $sum=$sum+$this->time_to_sec($table["$out[target]"]);
                           // echo ",".$table["$out[target]"]."<br/>";

                        }


                        $sum=$this->sec_to_time($sum/$m);
                     // echo "Total:".$sum."<br/>";
                         $this->arrayVariable[$k]["ans"]=$sum;


                    }
                    else {
                        $this->arrayVariable[$k]["ans"]=$sum;
                        $m=0;
                        foreach($this->arraysqltable as $table) {
                            $m++;
                            $sum=$sum+$table["$out[target]"];
                        }
                        $this->arrayVariable[$k]["ans"]=$sum/$m;


                    }


                    break;
                case "DistinctCount":
                    break;
                case "Lowest":

                    foreach($this->arraysqltable as $table) {
                        $lowest=$table[$out["target"]];
                        if($table[$out["target"]]<$lowest) {
                            $lowest=$table[$out["target"]];
                        }
                        $this->arrayVariable[$k]["ans"]=$lowest;
                    }
                    break;
                case "Highest":
                    $out["ans"]=0;
                    foreach($this->arraysqltable as $table) {
                        if($table[$out["target"]]>$out["ans"]) {
                            $this->arrayVariable[$k]["ans"]=$table[$out["target"]];
                        }
                    }
                    break;
//### A Count for groups, as a variable. Not tested yet, but seemed to work in print_r()					
                case "Count":
					$value=$this->arrayVariable[$k]["ans"];
					if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
                       $value=0;
					$value++;
                    $this->arrayVariable[$k]["ans"]=$value;
				break;
//### End of modification
                default:
                    $out["target"]=0;		//other cases needed, temporary leave 0 if not suitable case
                    break;

            }
        }
    }


    public function outpage($out_method="I",$filename="") {
		
        if($this->lang=="cn") {
            if($this->arrayPageSetting["orientation"]=="P") {
                $this->pdf=new PDF_Unicode($this->arrayPageSetting["orientation"],'pt',array($this->arrayPageSetting["pageWidth"],$this->arrayPageSetting["pageHeight"]));
                $this->pdf->AddUniGBhwFont("uGB");
            }
            else {
                $this->pdf=new PDF_Unicode($this->arrayPageSetting["orientation"],'pt',array($this->arrayPageSetting["pageHeight"],$this->arrayPageSetting["pageWidth"]));
                $this->pdf->AddUniGBhwFont("uGB");
            }
        }
        else {
// removed the hard coded TCPDF to a varible that can be set in the constuctor
            if($this->pdflib=="TCPDF") {
			    $pdflibclass = $this->pdflibClass;
                if($this->arrayPageSetting["orientation"]=="P")
                    $this->pdf=new $pdflibclass($this->arrayPageSetting["orientation"],'pt',array($this->arrayPageSetting["pageWidth"],$this->arrayPageSetting["pageHeight"]));
                else
                    $this->pdf=new $pdflibclass($this->arrayPageSetting["orientation"],'pt',array($this->arrayPageSetting["pageHeight"],$this->arrayPageSetting["pageWidth"]));
                $this->pdf->setPrintHeader(false);
                $this->pdf->setPrintFooter(false);
            }elseif($this->pdflib=="FPDF") {
                if($this->arrayPageSetting["orientation"]=="P")
                    $this->pdf=new $pdflibclass($this->arrayPageSetting["orientation"],'pt',array($this->arrayPageSetting["pageWidth"],$this->arrayPageSetting["pageHeight"]));
                else
                    $this->pdf=new $pdflibclass($this->arrayPageSetting["orientation"],'pt',array($this->arrayPageSetting["pageHeight"],$this->arrayPageSetting["pageWidth"]));
            }
            elseif($this->pdflib=="XLS"){
                
// I added thi code back from 0.8c without having any idea how it works
            
                 include dirname(__FILE__)."/ExportXLS.inc.php";
                $xls= new ExportXLS($this,$filename);
                die;


            }
        }
        //$this->arrayPageSetting["language"]=$xml_path["language"];
        $this->pdf->SetLeftMargin($this->arrayPageSetting["leftMargin"]);
        $this->pdf->SetRightMargin($this->arrayPageSetting["rightMargin"]);
        $this->pdf->SetTopMargin($this->arrayPageSetting["topMargin"]);
        $this->pdf->SetAutoPageBreak(true,$this->arrayPageSetting["bottomMargin"]/2);
        $this->pdf->AliasNbPages();
    

        $this->global_pointer=0;

        foreach ($this->arrayband as $band) {
//            $this->currentband=$band["name"]; // to know current where current band in!
            switch($band["name"]) {
                case "title":
                  if($this->arraytitle[0]["height"]>0)
                    $this->title();
                    break;
                case "pageHeader":
                    if(!$this->newPageGroup) {
                        $headerY = $this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];
                        $this->pageHeader($headerY);
                    }else {
                        $this->pageHeaderNewPage();
                    }
                    break;
                case "detail":
                    if(!$this->newPageGroup) {
                        $this->detail();
                    }else {
                        $this->detailNewPage();
                        //$this->groupNewPage();
                    }
                    break;

                case "group":
                    $this->group_pointer=$band["groupExpression"];
                    $this->group_name=$band["gname"];
                    break;





                    default:
                break;

            }

        }

        if($filename=="")
            $filename=$this->arrayPageSetting["name"].".pdf";
        /**
         * not sure if this is good practive.
         * because the driver will handle this and keep it open for multiple requests
         * and close it if needed. MYSQL keeps the connection open over multiple http requests.
         * disconnecting is a large performance hit.
         */
        // $this->_database->disconnect($this->cndriver);
        return $this->pdf->Output($filename,$out_method);	//send out the complete page

    }
public function element_pieChart($data){

          $height=$data->chart->reportElement["height"];
          $width=$data->chart->reportElement["width"];
         $x=$data->chart->reportElement["x"];
         $y=$data->chart->reportElement["y"];
          $charttitle['position']=$data->chart->chartTitle['position'];

           $charttitle['text']=$data->chart->chartTitle->titleExpression;
          $chartsubtitle['text']=$data->chart->chartSubTitle->subtitleExpression;
          $chartLegendPos=$data->chart->chartLegend['position'];

          $dataset=$data->pieDataset->dataset->datasetRun['subDataset'];

          $seriesexp=$data->pieDataset->keyExpression;
          $valueexp=$data->pieDataset->valueExpression;
          $bb=$data->pieDataset->dataset->datasetRun['subDataset'];
          $sql=$this->arraysubdataset["$bb"]['sql'];

         // $ylabel=$data->linePlot->valueAxisLabelExpression;


          $param=array();
          foreach($data->categoryDataset->dataset->datasetRun->datasetParameter as $tag=>$value){
              $param[]=  array("$value[name]"=>$value->datasetParameterExpression);
          }
//          print_r($param);

         $this->pointer[]=array('type'=>'PieChart','x'=>$x,'y'=>$y,'height'=>$height,'width'=>$width,'charttitle'=>$charttitle,
            'chartsubtitle'=> $chartsubtitle,
               'chartLegendPos'=> $chartLegendPos,'dataset'=>$dataset,'seriesexp'=>$seriesexp,
            'valueexp'=>$valueexp,'param'=>$param,'sql'=>$sql,'ylabel'=>$ylabel);

    }
    public function element_pie3DChart($data){


    }

    public function element_Chart($data,$type){
   $seriesexp=array();
          $catexp=array();
          $valueexp=array();
          $labelexp=array();
          $height=$data->chart->reportElement["height"];
          $width=$data->chart->reportElement["width"];
         $x=$data->chart->reportElement["x"];
         $y=$data->chart->reportElement["y"];
          $charttitle['position']=$data->chart->chartTitle['position'];
                    $titlefontname=$data->chart->chartTitle->font['pdfFontName'];
          $titlefontsize=$data->chart->chartTitle->font['size'];
           $charttitle['text']=$data->chart->chartTitle->titleExpression;
          $chartsubtitle['text']=$data->chart->chartSubTitle->subtitleExpression;
          $chartLegendPos=$data->chart->chartLegend['position'];
          $dataset=$data->categoryDataset->dataset->datasetRun['subDataset'];
          $subcatdataset=$data->categoryDataset;
          //echo $subcatdataset;
          $i=0;
          foreach($subcatdataset as $cat => $catseries){
            foreach($catseries as $a => $series){
               if("$series->categoryExpression"!=''){
                array_push( $seriesexp,"$series->seriesExpression");
                array_push( $catexp,"$series->categoryExpression");
                array_push( $valueexp,"$series->valueExpression");
                array_push( $labelexp,"$series->labelExpression");
               }

            }

          }


          $bb=$data->categoryDataset->dataset->datasetRun['subDataset'];
          $sql=$this->arraysubdataset[$bb]['sql'];
          switch($type){
            case "barChart":
                $ylabel=$data->barPlot->valueAxisLabelExpression;
                $xlabel=$data->barPlot->categoryAxisLabelExpression;
                $maxy=$data->barPlot->rangeAxisMaxValueExpression;
                $miny=$data->barPlot->rangeAxisMinValueExpression;
                break;
            case "lineChart":
                $ylabel=$data->linePlot->valueAxisLabelExpression;
                $xlabel=$data->linePlot->categoryAxisLabelExpression;
                $maxy=$data->linePlot->rangeAxisMaxValueExpression;
                $miny=$data->linePlot->rangeAxisMinValueExpression;
                $showshape=$data->linePlot["isShowShapes"];
                break;
             case "stackedAreaChart":
                      $ylabel=$data->areaPlot->valueAxisLabelExpression;
                        $xlabel=$data->areaPlot->categoryAxisLabelExpression;
                        $maxy=$data->areaPlot->rangeAxisMaxValueExpression;
                        $miny=$data->areaPlot->rangeAxisMinValueExpression;
                        
                
                 break;
          }
          


          $param=array();
          foreach($data->categoryDataset->dataset->datasetRun->datasetParameter as $tag=>$value){
              $param[]=  array("$value[name]"=>$value->datasetParameterExpression);
          }
          if($maxy!='' && $miny!=''){
              $scalesetting=array(0=>array("Min"=>$miny,"Max"=>$maxy));
          }
          else
              $scalesetting="";

         $this->pointer[]=array('type'=>$type,'x'=>$x,'y'=>$y,'height'=>$height,'width'=>$width,'charttitle'=>$charttitle,
            'chartsubtitle'=> $chartsubtitle,
               'chartLegendPos'=> $chartLegendPos,'dataset'=>$dataset,'seriesexp'=>$seriesexp,
             'catexp'=>$catexp,'valueexp'=>$valueexp,'labelexp'=>$labelexp,'param'=>$param,'sql'=>$sql,'xlabel'=>$xlabel,'showshape'=>$showshape,
             'titlefontsize'=>$titlefontname,'titlefontsize'=>$titlefontsize,'scalesetting'=>$scalesetting);


    }
//    public function element_lineChart($data){
//
//           $seriesexp=array();
//          $catexp=array();
//          $valueexp=array();
//          $labelexp=array();
//          $height=$data->chart->reportElement["height"];
//          $width=$data->chart->reportElement["width"];
//         $x=$data->chart->reportElement["x"];
//         $y=$data->chart->reportElement["y"];
//          $charttitle['position']=$data->chart->chartTitle['position'];
//                    $titlefontname=$data->chart->chartTitle->font['pdfFontName'];
//          $titlefontsize=$data->chart->chartTitle->font['size'];
//           $charttitle['text']=$data->chart->chartTitle->titleExpression;
//          $chartsubtitle['text']=$data->chart->chartSubTitle->subtitleExpression;
//          $chartLegendPos=$data->chart->chartLegend['position'];
//          $dataset=$data->categoryDataset->dataset->datasetRun['subDataset'];
//          $subcatdataset=$data->categoryDataset;
//          //echo $subcatdataset;
//          $i=0;
//          foreach($subcatdataset as $cat => $catseries){
//            foreach($catseries as $a => $series){
//               if("$series->categoryExpression"!=''){
//                array_push( $seriesexp,"$series->seriesExpression");
//                array_push( $catexp,"$series->categoryExpression");
//                array_push( $valueexp,"$series->valueExpression");
//                array_push( $labelexp,"$series->labelExpression");
//               }
//
//            }
//
//          }
//
//
//          $bb=$data->categoryDataset->dataset->datasetRun['subDataset'];
//          $sql=$this->arraysubdataset[$bb]['sql'];
//          $ylabel=$data->linePlot->valueAxisLabelExpression;
//          $xlabel=$data->linePlot->categoryAxisLabelExpression;
//        $showshape=$data->linePlot["isShowShapes"];
//
//
//          $param=array();
//          foreach($data->categoryDataset->dataset->datasetRun->datasetParameter as $tag=>$value){
//              $param[]=  array("$value[name]"=>$value->datasetParameterExpression);
//          }
//          $maxy=$data->barPlot->rangeAxisMaxValueExpression;
//          $miny=$data->barPlot->rangeAxisMinValueExpression;
//          if($maxy!='' && $miny!=''){
//              $scalesetting=array(0=>array("Min"=>$miny,"Max"=>$maxy));
//          }
//          else
//              $scalesetting="";
//
//         $this->pointer[]=array('type'=>'LineChart','x'=>$x,'y'=>$y,'height'=>$height,'width'=>$width,'charttitle'=>$charttitle,
//            'chartsubtitle'=> $chartsubtitle,
//               'chartLegendPos'=> $chartLegendPos,'dataset'=>$dataset,'seriesexp'=>$seriesexp,
//             'catexp'=>$catexp,'valueexp'=>$valueexp,'labelexp'=>$labelexp,'param'=>$param,'sql'=>$sql,'xlabel'=>$xlabel,'showshape'=>$showshape,
//             'titlefontsize'=>$titlefontname,'titlefontsize'=>$titlefontsize,'scalesetting'=>$scalesetting);
//
//    }

//
//
//    public function element_barChart($data,$type='BarChart'){
//
//           $seriesexp=array();
//          $catexp=array();
//          $valueexp=array();
//          $labelexp=array();
//          $height=$data->chart->reportElement["height"];
//          $width=$data->chart->reportElement["width"];
//          $x=$data->chart->reportElement["x"];
//          $y=$data->chart->reportElement["y"];
//          $charttitle['position']=$data->chart->chartTitle['position'];
//          $titlefontname=$data->chart->chartTitle->font['pdfFontName'];
//          $titlefontsize=$data->chart->chartTitle->font['size'];
//          $charttitle['text']=$data->chart->chartTitle->titleExpression;
//          $chartsubtitle['text']=$data->chart->chartSubTitle->subtitleExpression;
//
//
//          $chartLegendPos=$data->chart->chartLegend['position'];
//          $dataset=$data->categoryDataset->dataset->datasetRun['subDataset'];
//          $subcatdataset=$data->categoryDataset;
//
//          //echo $subcatdataset;
//          foreach($subcatdataset as $cat => $catseries){
//            foreach($catseries as $a => $series){
//               if("$series->categoryExpression"!=''){
//                array_push( $seriesexp,"$series->seriesExpression");
//                array_push( $catexp,"$series->categoryExpression");
//                array_push( $valueexp,"$series->valueExpression");
//                array_push( $labelexp,"$series->labelExpression");
//               }
//            }
//
//          }
//
//          $bb=$data->categoryDataset->dataset->datasetRun['subDataset'];
//          $sql=$this->arraysubdataset[$bb]['sql'];
//         $ylabel=$data->barPlot->valueAxisLabelExpression;
//          $xlabel=$data->barPlot->categoryAxisLabelExpression;
//          $maxy=$data->barPlot->rangeAxisMaxValueExpression;
//          $miny=$data->barPlot->rangeAxisMinValueExpression;
//          if($maxy!='' && $miny!=''){
//              $scalesetting=array(0=>array("Min"=>$miny,"Max"=>$maxy));
//          }
//          else
//              $scalesetting="";
//
//          $param=array();
//          foreach($data->categoryDataset->dataset->datasetRun->datasetParameter as $tag=>$value){
//              $param[]=  array("$value[name]"=>$value->datasetParameterExpression);
//          }
////          print_r($param);
//
//         $this->pointer[]=array('type'=>$type,'x'=>$x,'y'=>$y,'height'=>$height,'width'=>$width,'charttitle'=>$charttitle,
//            'chartsubtitle'=> $chartsubtitle,
//               'chartLegendPos'=> $chartLegendPos,'dataset'=>$dataset,'seriesexp'=>$seriesexp,
//             'catexp'=>$catexp,'valueexp'=>$valueexp,'labelexp'=>$labelexp,'param'=>$param,'sql'=>$sql,'ylabel'=>$ylabel,'xlabel'=>$xlabel,
//             'titlefontsize'=>$titlefontname,'titlefontsize'=>$titlefontsize,'scalesetting'=>$scalesetting);
//
//    }


public function setChartColor(){

    $k=0;
$this->chart->setColorPalette($k,0,255,88);$k++;
$this->chart->setColorPalette($k,121,88,255);$k++;
$this->chart->setColorPalette($k,255,91,99);$k++;
$this->chart->setColorPalette($k,255,0,0);$k++;
$this->chart->setColorPalette($k,0,0,100);$k++;
$this->chart->setColorPalette($k,200,0,100);$k++;
$this->chart->setColorPalette($k,0,100,0);$k++;
$this->chart->setColorPalette($k,100,0,0);$k++;
$this->chart->setColorPalette($k,200,0,0);$k++;
$this->chart->setColorPalette($k,0,0,200);$k++;
$this->chart->setColorPalette($k,50,0,0);$k++;
$this->chart->setColorPalette($k,100,0,50);$k++;
$this->chart->setColorPalette($k,0,50,0);$k++;
$this->chart->setColorPalette($k,100,50,0);$k++;
$this->chart->setColorPalette($k,50,100,50);$k++;
$this->chart->setColorPalette($k,0,255,0);$k++;
$this->chart->setColorPalette($k,100,50,0);$k++;
$this->chart->setColorPalette($k,200,100,50);$k++;
$this->chart->setColorPalette($k,100,50,200);$k++;
$this->chart->setColorPalette($k,0,200,0);$k++;
$this->chart->setColorPalette($k,200,100,0);$k++;
$this->chart->setColorPalette($k,200,50,50);$k++;
$this->chart->setColorPalette($k,50,50,50);$k++;
$this->chart->setColorPalette($k,200,100,100);$k++;
$this->chart->setColorPalette($k,50,50,100);$k++;
$this->chart->setColorPalette($k,100,0,200);$k++;
$this->chart->setColorPalette($k,200,50,100);$k++;
$this->chart->setColorPalette($k,100,100,200);$k++;
$this->chart->setColorPalette($k,0,0,50);$k++;
$this->chart->setColorPalette($k,50,250,200);$k++;
$this->chart->setColorPalette($k,100,250,200);$k++;
$this->chart->setColorPalette($k,10,10,10);$k++;
$this->chart->setColorPalette($k,20,30,50);$k++;
$this->chart->setColorPalette($k,80,150,200);$k++;
$this->chart->setColorPalette($k,30,70,20);$k++;
$this->chart->setColorPalette($k,33,60,0);$k++;
$this->chart->setColorPalette($k,150,0,200);$k++;
$this->chart->setColorPalette($k,20,60,50);$k++;
$this->chart->setColorPalette($k,50,250,250);$k++;
$this->chart->setColorPalette($k,33,250,70);$k++;

}


public function showLineChart($data,$y_axis){
    global $tmpchartfolder,$pchartfolder;


    if($pchartfolder=="")
        $pchartfolder="./pchart2";
//echo "$pchartfolder/class/pData.class.php";die;

        include_once("$pchartfolder/class/pData.class.php");
        include_once("$pchartfolder/class/pDraw.class.php");
        include_once("$pchartfolder/class/pImage.class.php");

    if($tmpchartfolder=="")
         $tmpchartfolder=$pchartfolder."/cache";

     $w=$data['width']+0;
     $h=$data['height']+0;



     $legendpos=$data['chartLegendPos'];
     //$legendpos="Right";
     $seriesexp=$data['seriesexp'];
     $catexp=$data['catexp'];
     $valueexp=$data['valueexp'];
     $labelexp=$data['labelexp'];
     $ylabel=$data['ylabel'].'';
     $xlabel=$data['xlabel'].'';
     $ylabel = str_replace(array('"',"'"),'',$ylabel);
     $xlabel = str_replace(array('"',"'"),'',$xlabel);
     $scalesetting=$data['scalesetting'];


     $x=$data['x'];
     $y1=$data['y'];
     $legendx=0;
     $legendy=0;

    $titlefontname=$data['titlefontname'].'';
    $titlefontsize=$data['titlefontsize']+0;


    $DataSet = new pData();

    foreach($catexp as $a=>$b)
       $catexp1[]=  str_replace(array('"',"'"), '',$b);

    $n=0;

    $DataSet->addPoints($catexp1,'S00');
    $DataSet->setSerieDescription('S00','asdasd');

    //$DataSet->AddSerie('S0');
    //$DataSet->SetSerieName('S0',"Cat");
    $DataSet->setAbscissa('S00');
    $n=$n+1;

    $ds=trim($data['dataset']);


    if($ds!=""){
              $sql=$this->subdataset[$ds];
        $param=$data['param'];
        foreach($param as $p)
            foreach($p as $tag =>$value)
                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
            $sql=$this->changeSubDataSetSql($sql);

        }
    else
        $sql=$this->sql;
   $result =   $this->_database->query($sql); //query from db
   // $result = @mysql_query($sql);
    $chartdata=array();
    $i=0;
//echo $sql."<br/><br/>";
    $seriesname=array();
    while ($row =  $this->_database->next($result)) {   //

                $j=0;
                foreach($row as $key => $value){
                    //$chartdata[$j][$i]=$value;
                    if($value=='')
                        $value=0;
                    if($key==str_replace(array('$F{','}'),'',$seriesexp[0]))
                    array_push($seriesname,$value);
                    else
                    foreach($valueexp as $v => $y){
                     if($key==str_replace(array('$F{','}'),'',$y)){
                         $chartdata[$i][$j]=(int)$value;

                           $j++;
                     }
                    }





                }
            $i++;

            }
            if($i==0)
                return 0;
            foreach($seriesname as $s=>$v){

                    $DataSet->addPoints($chartdata[$s],"$v");
              //  $DataSet->AddSerie("$v");
            }
            $DataSet->setAxisName(0,$ylabel);




    $this->chart = new pImage($w,$h,$DataSet);
    //$c = new pChart($w,$h);
    //$this->setChartColor();
    $this->chart->drawRectangle(1,1,$w-2,$h-2);
    $legendfontsize=8;
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>$legendfontsize));


$Title=$data['charttitle']['text'];


      switch($legendpos){
             case "Top":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 //$legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                     $legendy=$graphareay1+5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $legendy=$graphareay1+5;
                    $graphareax1=40;

                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;

                }
                 break;
             case "Left":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=40;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Right":
             $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$w-$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Bottom":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;
             default:
               $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;

         }


         //echo "$graphareax1,$graphareay1,$graphareax2,$graphareay2";die;
    //print_r($lgsize);die;

    $this->chart->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>8));



    //if($type=='StackedBarChart')
      //  $scalesetting=array("Floating"=>TRUE,"GridR"=>200, "GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE,
        //    "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,"DrawArrows"=>TRUE,"ArrowSize"=>6);
    //else
    $ScaleSpacing=5;
        $scalesetting= $scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,
            "GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE,"Mode"=>SCALE_MODE_START0,'ScaleSpacing'=>$ScaleSpacing);

    $this->chart->drawScale($scalesetting);

    $this->chart->drawLegend($legendx,$legendy,$legendmode);


    $Title = str_replace(array('"',"'"),'',$data['charttitle']['text']);

    if($Title!=''){
        $titlefontsize+0;
    if($titlefontsize==0)
        $titlefontsize=8;
     if($titlefontname=='')
        $titlefontname='calibri';
$titlefontname=strtolower($titlefontname);


    $textsetting=array('DrawBox'=>FALSE,'FontSize'=>$titlefontsize,'FontName'=>"$pchartfolder/fonts/".$titlefontname.".ttf",'align'=>TEXT_ALIGN_TOPMIDDLE);

    $this->chart->drawText($w/3,($titlefontsize+10),$Title,$textsetting);
    }

      $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>7));

         $this->chart->drawLineChart();


   $randomchartno=rand();
	  $photofile="$tmpchartfolder/chart$randomchartno.png";

             $this->chart->Render($photofile);
			
             if(file_exists($photofile)){
                $this->pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w,$h,"PNG");
                unlink($photofile);
             }

}



public function showBarChart($data,$y_axis,$type='barChart'){
      global $tmpchartfolder,$pchartfolder;


    if($pchartfolder=="")
        $pchartfolder="./pchart2";
//echo "$pchartfolder/class/pData.class.php";die;

        include_once("$pchartfolder/class/pData.class.php");
        include_once("$pchartfolder/class/pDraw.class.php");
        include_once("$pchartfolder/class/pImage.class.php");

    if($tmpchartfolder=="")
         $tmpchartfolder=$pchartfolder."/cache";

     $w=$data['width']+0;
     $h=$data['height']+0;



     $legendpos=$data['chartLegendPos'];
     //$legendpos="Right";
     $seriesexp=$data['seriesexp'];
     $catexp=$data['catexp'];
     $valueexp=$data['valueexp'];
     $labelexp=$data['labelexp'];
     $ylabel=$data['ylabel'].'';
     $xlabel=$data['xlabel'].'';
     $ylabel = str_replace(array('"',"'"),'',$ylabel);
     $xlabel = str_replace(array('"',"'"),'',$xlabel);
     $scalesetting=$data['scalesetting'];


     $x=$data['x'];
     $y1=$data['y'];
     $legendx=0;
     $legendy=0;
    $titlefontname=$data['titlefontname'].'';
    $titlefontsize=$data['titlefontsize']+0;


    $DataSet = new pData();

    foreach($catexp as $a=>$b)
       $catexp1[]=  str_replace(array('"',"'"), '',$b);

    $n=0;

    $DataSet->addPoints($catexp1,'S00');
    $DataSet->setSerieDescription('S00','asdasd');

    //$DataSet->AddSerie('S0');
    //$DataSet->SetSerieName('S0',"Cat");
    $DataSet->setAbscissa('S00');
    $n=$n+1;

    $ds=trim($data['dataset']);


    if($ds!=""){
              $sql=$this->subdataset[$ds];
        $param=$data['param'];
        foreach($param as $p)
            foreach($p as $tag =>$value)
                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
            $sql=$this->changeSubDataSetSql($sql);

        }
    else
        $sql=$this->sql;

   $result =   $this->_database->query($sql); //query from db
  
   
    $chartdata=array();
    $i=0;
//echo $sql."<br/><br/>";
    $seriesname=array();
   while ($row =  $this->_database->next($result)) {   

                $j=0;
                foreach($row as $key => $value){
                    //$chartdata[$j][$i]=$value;
                    if($value=='')
                        $value=0;
                    if($key==str_replace(array('$F{','}'),'',$seriesexp[0]))
                    array_push($seriesname,$value);
                    else
                    foreach($valueexp as $v => $y){
                     if($key==str_replace(array('$F{','}'),'',$y)){
                         $chartdata[$i][$j]=(int)$value;

                           $j++;
                     }
                    }





                }
            $i++;

            }
            if($i==0)
                return 0;
            foreach($seriesname as $s=>$v){

                    $DataSet->addPoints($chartdata[$s],"$v");
              //  $DataSet->AddSerie("$v");
            }
            $DataSet->setAxisName(0,$ylabel);




    $this->chart = new pImage($w,$h,$DataSet);
    //$c = new pChart($w,$h);
    //$this->setChartColor();
    $this->chart->drawRectangle(1,1,$w-2,$h-2);
    $legendfontsize=8;
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>$legendfontsize));


 $Title=$data['charttitle']['text'];


      switch($legendpos){
             case "Top":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 //$legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=15;
                     $legendy=$graphareay1+5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $legendy=$graphareay1+5;
                    $graphareax1=40;

                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;

                }
                 break;
             case "Left":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=10;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=40;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Right":
             $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$w-$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Bottom":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=15;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;
             default:
               $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=15;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;

         }


         //echo "$graphareax1,$graphareay1,$graphareax2,$graphareay2";die;
    //print_r($lgsize);die;

    $this->chart->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>8));


if($type=='stackedBarChart')
        $scalesetting=array("Floating"=>TRUE,"GridR"=>200, "GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE,
            "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,"ArrowSize"=>6);
    else
            $scalesetting=array("Floating"=>TRUE,"GridR"=>200, "GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE,
            "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_START0,"ArrowSize"=>6);
    $this->chart->drawScale($scalesetting);

    $this->chart->drawLegend($legendx,$legendy,$legendmode);


    $Title = str_replace(array('"',"'"),'',$data['charttitle']['text']);

    if($Title!=''){
        $titlefontsize+0;
    if($titlefontsize==0)
        $titlefontsize=8;
     if($titlefontname=='')
        $titlefontname='calibri';
$titlefontname=strtolower($titlefontname);

    $textsetting=array('DrawBox'=>FALSE,'FontSize'=>$titlefontsize,'FontName'=>"$pchartfolder/fonts/".$titlefontname.".ttf",'align'=>TEXT_ALIGN_TOPMIDDLE);
//print_r($textsetting);die;
    $this->chart->drawText($w/3,($titlefontsize+10),$Title,$textsetting);
    }

      $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>7));


    if($type=='stackedBarChart')
        $this->chart->drawStackedBarChart();
    else
        $this->chart->drawBarChart();


   $randomchartno=rand();
	  $photofile="$tmpchartfolder/chart$randomchartno.png";

             $this->chart->Render($photofile);

             if(file_exists($photofile)){
                $this->pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w,$h,"PNG");
                unlink($photofile);
             }


}




public function showAreaChart($data,$y_axis,$type){
    global $tmpchartfolder,$pchartfolder;


    if($pchartfolder=="")
        $pchartfolder="./pchart2";
//echo "$pchartfolder/class/pData.class.php";die;

        include_once("$pchartfolder/class/pData.class.php");
        include_once("$pchartfolder/class/pDraw.class.php");
        include_once("$pchartfolder/class/pImage.class.php");

    if($tmpchartfolder=="")
         $tmpchartfolder=$pchartfolder."/cache";

     $w=$data['width']+0;
     $h=$data['height']+0;



     $legendpos=$data['chartLegendPos'];
     //$legendpos="Right";
     $seriesexp=$data['seriesexp'];
     $catexp=$data['catexp'];
     $valueexp=$data['valueexp'];
     $labelexp=$data['labelexp'];
     $ylabel=$data['ylabel'].'';
     $xlabel=$data['xlabel'].'';
     $ylabel = str_replace(array('"',"'"),'',$ylabel);
     $xlabel = str_replace(array('"',"'"),'',$xlabel);
     $scalesetting=$data['scalesetting'];


     $x=$data['x'];
     $y1=$data['y'];
     $legendx=0;
     $legendy=0;

    $titlefontname=$data['titlefontname'].'';
    $titlefontsize=$data['titlefontsize']+0;


    $DataSet = new pData();

    foreach($catexp as $a=>$b)
       $catexp1[]=  str_replace(array('"',"'"), '',$b);

    $n=0;

    $DataSet->addPoints($catexp1,'S00');
    $DataSet->setSerieDescription('S00','asdasd');

    //$DataSet->AddSerie('S0');
    //$DataSet->SetSerieName('S0',"Cat");
    $DataSet->setAbscissa('S00');
    $n=$n+1;

    $ds=trim($data['dataset']);


    if($ds!=""){
              $sql=$this->subdataset[$ds];
        $param=$data['param'];
        foreach($param as $p)
            foreach($p as $tag =>$value)
                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
            $sql=$this->changeSubDataSetSql($sql);

        }
    else
        $sql=$this->sql;

    $result = $this->_database->query($sql);
    $chartdata=array();
    $i=0;
//echo $sql."<br/><br/>";
    $seriesname=array();
 while ($row =  $this->_database->next($result)) {   //

                $j=0;
                foreach($row as $key => $value){
                    //$chartdata[$j][$i]=$value;
                    if($value=='')
                        $value=0;
                    if($key==str_replace(array('$F{','}'),'',$seriesexp[0]))
                    array_push($seriesname,$value);
                    else
                    foreach($valueexp as $v => $y){
                     if($key==str_replace(array('$F{','}'),'',$y)){
                         $chartdata[$i][$j]=(int)$value;

                           $j++;
                     }
                    }





                }
            $i++;

            }
            if($i==0)
                return 0;
            foreach($seriesname as $s=>$v){

                    $DataSet->addPoints($chartdata[$s],"$v");
              //  $DataSet->AddSerie("$v");
            }
            $DataSet->setAxisName(0,$ylabel);




    $this->chart = new pImage($w,$h,$DataSet);
    //$c = new pChart($w,$h);
    //$this->setChartColor();
    $this->chart->drawRectangle(1,1,$w-2,$h-2);
    $legendfontsize=8;
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>$legendfontsize));


$Title=$data['charttitle']['text'];


      switch($legendpos){
             case "Top":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 //$legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                     $legendy=$graphareay1+5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $legendy=$graphareay1+5;
                    $graphareax1=40;

                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;

                }
                 break;
             case "Left":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=40;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Right":
             $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$w-$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Bottom":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;
             default:
               $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;

         }


         //echo "$graphareax1,$graphareay1,$graphareax2,$graphareay2";die;
    //print_r($lgsize);die;

    $this->chart->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>8));



    //if($type=='StackedBarChart')
      //  $scalesetting=array("Floating"=>TRUE,"GridR"=>200, "GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE,
        //    "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,"DrawArrows"=>TRUE,"ArrowSize"=>6);
    //else
    $ScaleSpacing=5;
        $scalesetting= $scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,
            "GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,'ScaleSpacing'=>$ScaleSpacing);

    $this->chart->drawScale($scalesetting);

    $this->chart->drawLegend($legendx,$legendy,$legendmode);


    $Title = str_replace(array('"',"'"),'',$data['charttitle']['text']);

    if($Title!=''){
        $titlefontsize+0;
    if($titlefontsize==0)
        $titlefontsize=8;
     if($titlefontname=='')
        $titlefontname='calibri';
$titlefontname=strtolower($titlefontname);


    $textsetting=array('DrawBox'=>FALSE,'FontSize'=>$titlefontsize,'FontName'=>"$pchartfolder/fonts/".$titlefontname.".ttf",'align'=>TEXT_ALIGN_TOPMIDDLE);

    $this->chart->drawText($w/3,($titlefontsize+10),$Title,$textsetting);
    }

      $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>7));

$this->chart->drawStackedAreaChart(array("Surrounding"=>60));


   $randomchartno=rand();
	  $photofile="$tmpchartfolder/chart$randomchartno.png";

             $this->chart->Render($photofile);

             if(file_exists($photofile)){
                $this->pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w,$h,"PNG");
                unlink($photofile);
             }

}




private function changeSubDataSetSql($sql){

foreach($this->currentrow as $name =>$value)
        $sql=str_replace('$F{'.$name.'}',$value,$sql);

foreach($this->arrayParameter as $name=>$value)
    $sql=str_replace('$P{'.$name.'}',$value,$sql);

foreach($this->arrayVariable as $name=>$value){
    $sql=str_replace('$V{'.$value['target'].'}',$value['ans'],$sql);


}


//print_r($this->arrayparameter);


//variable not yet implemented
     return $sql;


}
    public function background() {
        foreach ($this->arraybackground as $out) {
            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$this->arrayPageSetting["topMargin"],true);
                    break;
                default:
                    $this->display($out,$this->arrayPageSetting["topMargin"],false);
                    break;
            }

        }
    }

    public function pageHeader($headerY) {
        $this->currentband='pageHeader';// to know current where current band in!
        $this->pdf->AddPage();
		$this->background();
        if(isset($this->arraypageHeader)) {
            $this->arraypageHeader[0]["y_axis"]=$this->arrayPageSetting["topMargin"];
        }
        foreach ($this->arraypageHeader as $out) {
            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$this->arraypageHeader[0]["y_axis"],true);
                    break;
                default:
                    $this->display($out,$this->arraypageHeader[0]["y_axis"],false);
                    break;
            }
        }
    
        $this->currentband='';
    }

    public function pageHeaderNewPage() {
        $this->currentband='pageHeader';
        $this->pdf->AddPage();
        $this->background();
        if(isset($this->arraypageHeader)) {
            $this->arraypageHeader[0]["y_axis"]=$this->arrayPageSetting["topMargin"];
        }
        foreach ($this->arraypageHeader as $out) {
            switch($out["hidden_type"]) {
                case "textfield":
                    $this->display($out,$this->arraypageHeader[0]["y_axis"],true);
                    break;
                default:
                    $this->display($out,$this->arraypageHeader[0]["y_axis"],true);
                    break;
            }
        } 
        $this->showGroupHeader($this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]);
    }


    public function title() {
                $this->currentband='title';
        $this->pdf->AddPage();
        $this->background();

            $this->titleheight=$this->arraytitle[0]["height"];

            //print_r($this->arraytitle);die;
        if(isset($this->arraytitle)) {
            $this->arraytitle[0]["y_axis"]=$this->arrayPageSetting["topMargin"];
        }

        foreach ($this->arraytitle as $out) {

            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$this->arraytitle[0]["y_axis"],true);
                    break;
                default:
                    $this->display($out,$this->arraytitle[0]["y_axis"],false);
                    break;
            }
        }

        $this->currentband='';
    }

      public function summary($y) {
        //$this->pdf->AddPage();
        //$this->background();
            $this->currentband='summary';
            $this->titlesummary=$this->arraysummary[0]["height"];

            //print_r($this->arraytitle);die;

        foreach ($this->arraysummary as $out) {

            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$y,true);
                    break;
                default:
                    $this->display($out,$y,false);
                    break;
            }
        }

                $this->currentband='';
    }

    public function group($headerY) {


        $gname=$this->arrayband[0]["gname"]."";
        if(isset($this->arraypageHeader)) {
            $this->arraygroup[$gname]["groupHeader"][0]["y_axis"]=$headerY;
        }
        if(isset($this->arraypageFooter)) {
            $this->arraygroup[$gname]["groupFooter"][0]["y_axis"]=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]-$this->arraygroup[$gname]["groupFooter"][0]["height"];
        }
        else {
            $this->arraygroup[$gname]["groupFooter"][0]["y_axis"]=$this->arrayPageSetting["pageHeight"]-$this->arrayPageSetting["bottomMargin"]-$this->arraygroup[$gname]["groupFooter"][0]["height"];
        }

        if(isset($this->arraygroup)) {

            foreach($this->arraygroup[$gname] as $name=>$out) {


                switch($name) {
                    case "groupHeader":
//###                        $this->group_count=0;
                        foreach($out as $path) { //print_r($out);
                            switch($path["hidden_type"]) {
                                case "field":

                                    $this->display($path,$this->arraygroup[$gname]["groupHeader"][0]["y_axis"],true);
                                    break;
                                default:

                                    $this->display($path,$this->arraygroup[$gname]["groupHeader"][0]["y_axis"],false);
                                    break;
                            }
                        }
                        break;
                    case "groupFooter":
                        foreach($out as $path) {
                            switch($path["hidden_type"]) {
                                case "field":
                                    $this->display($path,$this->arraygroup[$gname]["groupFooter"][0]["y_axis"],true);
                                    break;
                                default:
                                    $this->display($path,$this->arraygroup[$gname]["groupFooter"][0]["y_axis"],false);
                                    break;
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
        }
    }


    public function groupNewPage() {
        $gname=$this->arrayband[0]["gname"]."";

        if(isset($this->arraypageHeader)) {
            $this->arraygroup[$gname]["groupHeader"][0]["y_axis"]=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];
        }
        if(isset($this->arraypageFooter)) {
            $this->arraygroup[$gname]["groupFooter"][0]["y_axis"]=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]-$this->arraygroup[$gname]["groupFooter"][0]["height"];
        }
        else {
            $this->arraygroup[$gname]["groupFooter"][0]["y_axis"]=$this->arrayPageSetting["pageHeight"]-$this->arrayPageSetting["bottomMargin"]-$this->arraygroup[$gname]["groupFooter"][0]["height"];
        }

        if(isset($this->arraygroup)) {
            foreach($this->arraygroup[$gname] as $name=>$out) {
                switch($name) {
                    case "groupHeader":
                        foreach($out as $path) {
                            switch($path["hidden_type"]) {
                                case "field":
                                    $this->display($path,$this->arraygroup[$gname]["groupHeader"][0]["y_axis"],true);
                                    break;
                                default:

                                    $this->display($path,$this->arraygroup[$gname]["groupHeader"][0]["y_axis"],false);
                                    break;
                            }
                        }
                        break;
                    case "groupFooter":
                        foreach($out as $path) {
                            switch($path["hidden_type"]) {
                                case "field":
                                    $this->display($path,$this->arraygroup[$gname]["groupFooter"][0]["y_axis"],true);
                                    break;
                                default:
                                    $this->display($path,$this->arraygroup[$gname]["groupFooter"][0]["y_axis"],false);
                                    break;
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
        }
    }

    public function pageFooter() {
        $this->currentband='pageFooter';
        if(isset($this->arraypageFooter)) {
            foreach ($this->arraypageFooter as $out) {
                switch($out["hidden_type"]) {
                    case "field":
                        $this->display($out,$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],true);
                        break;
                    default:
                        $this->display($out,$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],false);
                        break;
                }
            }
        }
        else {
            $this->lastPageFooter();
        }
        $this->currentband='';
    }

    public function lastPageFooter() {
        $this->currentband='lastPageFooter';
        if(isset($this->arraylastPageFooter)) {
            foreach ($this->arraylastPageFooter as $out) {
                switch($out["hidden_type"]) {
                    case "field":
                        $this->display($out,$this->arrayPageSetting["pageHeight"]-$this->arraylastPageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],true);
                        break;
                    default:
                        $this->display($out,$this->arrayPageSetting["pageHeight"]-$this->arraylastPageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],false);
                        break;
                }
            }
        }
        $this->currentband='';
    }

    public function NbLines($w,$txt) {
        //Computes the number of lines a MultiCell of width w will take
        $cw=&$this->pdf->CurrentFont['cw'];
        if($w==0)
            $w=$this->pdf->w-$this->pdf->rMargin-$this->pdf->x;
        $wmax=($w-2*$this->pdf->cMargin)*1000/$this->pdf->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb) {
            $c=$s[$i];
            if($c=="\n") {
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax) {
                if($sep==-1) {
                    if($i==$j)
                        $i++;
                }
                else
                    $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }
    

	public function printlongtext($fontfamily,$fontstyle,$fontsize){
					//$this->gotTextOverPage=false;
						
							$this->pageFooter();
						$this->pageHeader();
					$this->hideheader==true;
					
					$this->currentband='detail';  
   // remaps jasper font names to the odd TCPDF font names 
					$this->pdf->SetFont($this->getFontMap($fontfamily),$fontstyle,$fontsize);
					$this->pdf->SetTextColor($this->forcetextcolor_r,$this->forcetextcolor_g,$this->forcetextcolor_b);
					//$this->pdf->SetTextColor(44,123,4);
					$this->pdf->SetFillColor($this->forcefillcolor_r,$this->forcefillcolor_g,$this->forcefillcolor_b);

					$bltxt=$this->continuenextpageText; 
					$this->pdf->SetY($this->arraypageHeader[0]["height"]+15);
					$this->pdf->SetX($bltxt['x']);
					$maxheight=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->pdf->GetY()-$bltxt['height'];

					$this->pdf->MultiCell($bltxt['width'],$bltxt['height'],$bltxt['txt'],
								$bltxt['border'],
								$bltxt['align'],$bltxt['fill'],$bltxt['ln'],'','',$bltxt['reset'],
								$bltxt['streth'],$bltxt['ishtml'],$bltxt['autopadding'],$maxheight-$bltxt['height']);
							
// you guess is good as mine what this does
					if($this->pdf->balancetext!=''){
							$this->continuenextpageText=array('width'=>$bltxt["width"], 'height'=>$bltxt["height"], 
								'txt'=>$this->pdf->balancetext,	'border'=>$bltxt["border"] ,'align'=>$bltxt["align"], 'fill'=>$bltxt["fill"],'ln'=>1,
										'x'=>$bltxt['x'],'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,'autopadding'=>true);
								$this->pdf->balancetext='';
								$this->printlongtext($fontfamily,$fontstyle,$fontsize);
					}
					//echo $this->currentband;  
				if( $this->pdf->balancetext=='' && $this->currentband=='detail'){
					if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
						$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					else{
						if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
								$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					}
					
				}
		}
		
		
    public function detail() {
		
		$currentpage= $this->pdf->getNumPages();
		$this->maxpagey=array();
        $this->currentband='detail';
   
        $this->arraydetail[0]["y_axis"]=$this->arraydetail[0]["y_axis"]- $this->titleheight;
        $field_pos_y=$this->arraydetail[0]["y_axis"];
        $biggestY=0;
        $tempY=$this->arraydetail[0]["y_axis"];
        
        if(isset($this->SubReportCheckPoint))
			$checkpoint=$this->SubReportCheckPoint;
      //  else
		//	$checkpoint=$this->arraydetail[0]["y_axis"];
			$checkpoint=$this->arraydetail[0]["y_axis"];
			
// set fixed sizes to be used later
$pageheight=$this->arrayPageSetting["pageHeight"];
		$footerheight=$this->footerbandheight;
		$headerheight=$this->headerbandheight;
		$bottommargin=$this->arrayPageSetting["bottomMargin"];
		$detailheight=$this->detailbandheight;

		//		$this->pdf->SetY($checkpoint);
	//	$this->pdf->MultiCell(200,10,"====",1);

	// render footer if at bottom of page and then render header on next page
       if($checkpoint>= $pageheight- $footerheight -$bottommargin - ($this->arraygrouphead[0]['height'] * 1.5)-1)
		 {
						$this->pageFooter();
						$this->pageHeader();
				 
 }
 
 	    $gheader=$this->showGroupHeader($this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]);
 
		$isgroupfooterprinted=false;
//		$this->pdf->SetY($checkpoint+$gheader);
	//	$this->pdf->MultiCell(200,10,"????",1);
		$this->maxpagey=array('page_0'=>$checkpoint);
        $rownum=0; 
        
        if($this->arraysqltable) {
		$n=0;
// loop though band but skip if not match $filterExpressio
            while($row = $this->arraysqltable[$this->global_pointer] ) {
	         $filterExpression_result =	$this->analyse_expression($this->filterExpression);  
		//	var_dump( $filterExpression_result);
			if ($filterExpression_result )
			  {
		
		//	$this->currentband='detail';
   			$n++;
				$currentpage= $this->pdf->getNumPages();
				
				$this->pdf->lastPage();
				$this->hideheader==false;
					
				if($n>1)
					$checkpoint=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)];
      //                   echo $checkpoint."<br/>";

		$pageheight=$this->arrayPageSetting["pageHeight"];
		$footerheight=$this->footerbandheight;
		$headerheight=$this->headerbandheight;
		$bottommargin=$this->arrayPageSetting["bottomMargin"];
		$detailheight=$this->detailbandheight;
		
		//if content near page footer

// caclulated band hieght i think
	             if(isset($this->arrayVariable))	//if self define variable existing, go to do the calculation
                    $this->variable_calculation($rownum, $this->arraysqltable[$this->global_pointer][$this->group_pointer]);
             
                     if($checkpoint>= $pageheight- $footerheight -$bottommargin - ($detailheight * 1.5)-1){
						$this->pageFooter();
						$this->pageHeader();
						$currentpage= $this->pdf->getNumPages();
						$ghheight = $this->showGroupHeader($this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]);
                        $isgroupfooterprinted=false;
					
					    $isheaderforpageout = true;
						$checkpoint=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"] + $ghheight;//$this->arraydetail[0]["y_axis"]- $this->titleheight;
						$this->maxpagey[($this->pdf->getPage()-1)]=$checkpoint;
			        }
			   	else
				if(isset($this->arraygroup)&&($this->global_pointer>0)&&
                        ($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])){	//check the group's groupExpression existed and same or not

					if($isgroupfooterprinted==true){
						$gfoot=0;
					}
					
				     if ( isset($this->arraysqltable[$this->global_pointer]))
                        $ghheight=$this->showGroupHeader($checkpoint+$gfoot);
                   $isgroupfooterprinted=false;
				  $isheaderforpageout = false;
					$checkpoint=$checkpoint+$gfoot+$ghheight;//after group header add height band, so detail no crash with group header.	
                   $this->footershowed=true;
                    $this->pdf->SetY($checkpoint); 
         			$this->group_count["$this->group_name"]=1;	// We're on the first row of the group.				 
		
                }
				
			    $isheaderforpageout = false; 


		$this->currentband='detail';
	
/* begin page handling*/


//begin page handling
$biggestY = 0;
                foreach ($this->arraydetail as $out) {
//						echo $out["hidden_type"]."<br/>";

// when ever a items height is higher that the current heighest it because the current heighest
// this is how we know the size of the band
                   if($this->pdf->GetY() > $biggestY) {
                                $biggestY = $this->pdf->GetY();
                            }
                    switch ($out["hidden_type"]) {
                        case "field":
                     //        $txt=$this->analyse_expression($compare["txt"]);

							$maxheight=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->pdf->GetY()-15;
                            $this->prepare_print_array=array("type"=>"MultiCell","width"=>$out["width"],"height"=>$out["height"],"txt"=>$out["txt"],
									"border"=>$out["border"],"align"=>$out["align"],"valign"=>$out["valign"],"fill"=>$out["fill"],"hidden_type"=>$out["hidden_type"],
									"printWhenExpression"=>$out["printWhenExpression"],"soverflow"=>$out["soverflow"],"poverflow"=>$out["poverflow"],"link"=>$out["link"],
									"pattern"=>$out["pattern"],"writeHTML"=>$out["writeHTML"],"isPrintRepeatedValues"=>$out["isPrintRepeatedValues"]);
                           
						   $this->display($this->prepare_print_array,0,true,$maxheight);
              //                                  $checkpoint=$this->arraydetail[0]["y_axis"];

					        break;
                        case "relativebottomline":
                        //$this->relativebottomline($out,$tempY);
						
                            $this->relativebottomline($out,$biggestY);
                            break;
                          case "subreport":
                            $this->display($out,$checkpoint);
						 break;
                        default:
							//echo $out["hidden_type"]."=".print_r($out,true)."<br/><br/>";
                          $this->display($out,$checkpoint);
							
							$maxheight=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->pdf->GetY()-15;
				//				$checkpoint=$this->arraydetail[0]["y_axis"];

                            //$checkpoint=$this->pdf->GetY();
                            break;
                    }
                    
                    $this->pdf->setPage($currentpage);

                }

				$this->pdf->lastPage();
								
//                if($this->SubReportCheckPoint>0)
	//				$biggestY=$this->SubReportCheckPoint;
		//			$this->SubReportCheckPoint=0; //if subreport return position

        if(isset($this->arraygroup)&&
           ($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer+1][$this->group_pointer])){




		$pageheight=$this->arrayPageSetting["pageHeight"];
		$footerheight=$this->footerbandheight;
		$headerheight=$this->headerbandheight;
		$topmargin=$this->arrayPageSetting["topMargin"];
		$bottommargin=$this->arrayPageSetting["bottomMargin"];
		$detailheight=$this->detailbandheight;
		$gfootheight=$this->arraygroupfoot[0]['height'];
		$currentY=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)];
		
		if(($currentY+$gfootheight) < ($pageheight-$bottommargin-$footerheight)){
                         
							$gfoot= $this->showGroupFooter($this->maxpagey['page_'.($this->pdf->getPage()-1)]);
							$checkpoint=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)]+$gfoot;
						  				      
						}else{
						$this->pageFooter();
						$hhead=	$this->pageHeader($this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]);
	//					  $checkpoint=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)]+$gfoot;						      
                         $gfoot= $this->showGroupFooter($headerheight+$this->arrayPageSetting["topMargin"]);
                         $isgroupfooterprinted=true;
						//	$this->pdf->Cell(100,30,"New pages footer",1);
						  $checkpoint=$gfoot+$headerheight+$this->arrayPageSetting["topMargin"];//$hhead+$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]+$gfoot;						      
							$this->maxpagey['page_'.($this->pdf->getNumPages()-1)]=	$checkpoint;
						
							}  
                        $this->currentband='detail';
   			            }

				foreach($this->group_count as &$cntval) {
					$cntval++;
				}
				$this->report_count++;
				}
                $this->global_pointer++;
                   $rownum++;			   
				  $this->pdf->lastpage();
				  $headerY=$checkpoint;            
            
            }
        
        
					$this->global_pointer--;
        }else {
            echo "No data found";
            exit(0);
        }
 
 
 			



//        $this->global_pointer--;
           if($this->arraysummary[0]["height"]>0)
                    $this->summary($checkpoint);
 
        if(isset($this->arraylastPageFooter))
            $this->lastPageFooter();
        else
             $this->pageFooter();
       
        $this->currentband='';
        
   			
    }
    
        public function detailNewPage() {
		$currentpage= $this->pdf->getNumPages();
		$this->maxpagey=array();
        $this->currentband='detail';
   
        $this->arraydetail[0]["y_axis"]=$this->arraydetail[0]["y_axis"]- $this->titleheight;
        $field_pos_y=$this->arraydetail[0]["y_axis"];
        $biggestY=0;
        $tempY=$this->arraydetail[0]["y_axis"];
        
        if(isset($this->SubReportCheckPoint))
			$checkpoint=$this->SubReportCheckPoint;
//        else
	//		$checkpoint=$this->arraydetail[0]["y_axis"];
			$checkpoint=$this->arraydetail[0]["y_axis"];

//		$this->pdf->SetY($checkpoint);
	//	$this->pdf->MultiCell(200,10,"====",1);

	//	$isgroupfooterprinted=false;

//		$this->pdf->SetY($checkpoint+$gheader);
	//	$this->pdf->MultiCell(200,10,"????",1);
		$this->maxpagey=array('page_0'=>$checkpoint);
        $rownum=0; 
        
        if($this->arraysqltable) {
		$n=0;
            foreach($this->arraysqltable as $row) {
	
	
		//	$this->currentband='detail';
   			$n++;
				$currentpage= $this->pdf->getNumPages();
				
				$this->pdf->lastPage();
				if($n>1)
					$checkpoint=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)];
      //                   echo $checkpoint."<br/>";

		$pageheight=$this->arrayPageSetting["pageHeight"];
		$footerheight=$this->footerbandheight;
		$headerheight=$this->headerbandheight;
		$bottommargin=$this->arrayPageSetting["bottomMargin"];
		$detailheight=$this->detailbandheight;
		$topmargin=$this->arrayPageSetting["topmargin"];

		
		//if content near page footer
		if($checkpoint>= $pageheight- $footerheight -$bottommargin- $detailheight-1){
						$this->pageFooter();
		                $this->pageHeaderNewPage();
						
						$this->pdf->lastpage();
						$currentpage= $this->pdf->getNumPages();
						$checkpoint=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]+$this->arraygrouphead[0]['height'];//$this->arraydetail[0]["y_axis"]- $this->titleheight;
	//	$this->pdf->Cell(300,10,"$currentY+$gfootheight > $pageheight-$bottommargin-$footerheight",1);
 						$this->maxpagey[($this->pdf->getPage()-1)]=$checkpoint;
			}

				if(isset($this->arrayVariable))	//if self define variable existing, go to do the calculation
                    $this->variable_calculation($rownum, $this->arraysqltable[$this->global_pointer][$this->group_pointer]);
                

				if(isset($this->arraygroup)&&($this->global_pointer>0)&&
                        ($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])){	//check the group's groupExpression existed and same or not
						$this->pageFooter();
                    $this->pageHeaderNewPage();
					$this->pdf->lastPage();
						$currentpage= $this->pdf->getNumPages();

					$checkpoint=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]+$this->arraygrouphead[0]['height'];
					$this->maxpagey[($this->pdf->getPage()-1)]=$checkpoint;
					
					$this->footershowed=true;
         			$this->group_count["$this->group_name"]=1;	// We're on the first row of the group.				 
		
                }


		$this->currentband='detail';
	
/* begin page handling*/

//                    $this->pdf->Cell(200,10,"?*$checkpoint"); 

//begin page handling
                foreach ($this->arraydetail as $out) {
//						echo $out["hidden_type"]."<br/>";
                    switch ($out["hidden_type"]) {
                        case "field":
                     //        $txt=$this->analyse_expression($compare["txt"]);
//                $this->pdf->SetY($checkpoint);
    
							$maxheight=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->pdf->GetY()-10;
                            $this->prepare_print_array=array("type"=>"MultiCell","width"=>$out["width"],"height"=>$out["height"],"txt"=>$out["txt"],
// add valign					
				"border"=>$out["border"],"align"=>$out["align"],"valign"=>$out["valign"],"fill"=>$out["fill"],"hidden_type"=>$out["hidden_type"],
									"printWhenExpression"=>$out["printWhenExpression"],"soverflow"=>$out["soverflow"],"poverflow"=>$out["poverflow"],"link"=>$out["link"],
									"pattern"=>$out["pattern"],"writeHTML"=>$out["writeHTML"],"isPrintRepeatedValues"=>$out["isPrintRepeatedValues"]);
//		$this->pdf->Cell(300,10,"==$checkpoint --",1);
                            $this->display($this->prepare_print_array,0,true,$maxheight);
              //                                  $checkpoint=$this->arraydetail[0]["y_axis"];

					        break;
                        case "relativebottomline":
                        //$this->relativebottomline($out,$tempY);
                            $this->relativebottomline($out,$biggestY);
                            break;
                          case "":
                          ;
                          break;
                        default:
                            $this->display($out,$checkpoint);
                            break;
                    }
                    
                    $this->pdf->setPage($currentpage);

                }

				$this->pdf->lastPage();
			  if($this->SubReportCheckPoint>0)
					$biggestY=$this->SubReportCheckPoint; $this->SubReportCheckPoint=0; //if subreport return position

        if(isset($this->arraygroup)&&
           ($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer+1][$this->group_pointer])){




		$pageheight=$this->arrayPageSetting["pageHeight"];
		$footerheight=$this->footerbandheight;
		$headerheight=$this->headerbandheight;
		$topmargin=$this->arrayPageSetting["topMargin"];
		$bottommargin=$this->arrayPageSetting["bottomMargin"];
		$detailheight=$this->detailbandheight;
		$gfootheight=$this->arraygroupfoot[0]['height'];
		$currentY=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)];
//		$this->pdf->Cell(300,10,"$currentY+$gfootheight > $pageheight-$bottommargin-$footerheight",1);


		if(($currentY+$gfootheight) < ($pageheight-$bottommargin-$footerheight)){
                         
							$gfoot= $this->showGroupFooter($this->maxpagey['page_'.($this->pdf->getPage()-1)]);
							$checkpoint=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)]+$gfoot;
						  				      
						}else{
							$gfoot= $this->showGroupFooter($this->maxpagey['page_'.($this->pdf->getPage()-1)]);
							$checkpoint=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)]+$gfoot;
			//			                    $this->pageHeader();

			//			$this->pageHeader($this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]);
      //                   $gfoot= $this->showGroupFooter($this->maxpagey['page_'.($this->pdf->getPage()-1)]);
		//				  $checkpoint=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)]+$gfoot;						      
				
							}  
                        $this->currentband='detail';
   			            }

				foreach($this->group_count as &$cntval) {
					$cntval++;
				}
				$this->report_count++;
				
                $this->global_pointer++;
                   $rownum++;			   
				  $this->pdf->lastpage();
				  $headerY=$checkpoint;            
            
            }
        
        
					$this->global_pointer--;
        }else {
            echo "No data found";
            exit(0);
        }
 
 
 			



        $this->global_pointer--;
           if($this->arraysummary[0]["height"]>0)
                    $this->summary($checkpoint);
 
        if(isset($this->arraylastPageFooter))
            $this->lastPageFooter();
        else
             $this->pageFooter();
       
        $this->currentband='';
        
   			
    }

/*
    public function detailNewPage() {
        $this->arraydetail[0]["y_axis"]=$this->arraydetail[0]["y_axis"]- $this->titleheight;

        $field_pos_y=$this->arraydetail[0]["y_axis"];
        $biggestY=0;
        $checkpoint=$this->arraydetail[0]["y_axis"];
        $tempY=$this->arraydetail[0]["y_axis"];
        $i=0;


        if($this->arraysqltable) {
            $oo=0;

            foreach($this->arraysqltable as $row) {
                $oo++;

                //check the group's groupExpression existed and same or not
                if(isset($this->arraygroup)&&($this->global_pointer>0)&&($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])) {

                if(isset($this->arrayVariable))	//if self define variable existing, go to do the calculation
                {
                    $this->variable_calculation($rownum, $this->arraysqltable[$this->global_pointer][$this->group_pointer]);
                }
                    $this->pageFooter();
                    $this->pageHeaderNewPage();
                    $checkpoint=$this->arraydetail[0]["y_axis"];
                    $biggestY = 0;
                    $tempY=$this->arraydetail[0]["y_axis"];
//###                     $this->group_count=0;
					$this->group_count["$this->group_name"]=1;
//### End of modification
                }

                foreach($this->arraydetail as $compare)	//this loop is to count possible biggest Y of the coming row
                {$this->currentrow=$this->arraysqltable[$this->global_pointer];
                    switch($compare["hidden_type"]) {
                        case "field":
                            $txt=$this->analyse_expression($row["$compare[txt]"]);
                            //check group footer existed or not

                            if(isset($this->arraygroup[$this->group_name]["groupFooter"])&&(($checkpoint+($compare["height"]*$txt))>($this->arrayPageSetting[pageHeight]-$this->arraygroup["$this->group_name"][groupFooter][0]["height"]-$this->arrayPageSetting["bottomMargin"]))) {
                             //   $this->showGroupHeader();
                                $this->showGroupFooter();
                                $this->pageFooter();
                               // $this->pdf->AddPage();
                             //   $this->background();
                                $this->pageHeaderNewPage();

                                $checkpoint=$this->arraydetail[0]["y_axis"];
                                $biggestY=0;
                                $tempY=$this->arraydetail[0]["y_axis"];
                            }
                            //check pagefooter existed or not
                            elseif(isset($this->arraypageFooter)&&(($checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt))))>($this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]))) {
                                $this->showGroupFooter();
                                $this->pageFooter();
                              //  $this->pdf->AddPage();
                                $this->pageHeaderNewPage();
                           //     $this->showGroupHeader();
                             //   $this->background();
                                $headerY = $this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];

                                $checkpoint=$this->arraydetail[0]["y_axis"];
                                $biggestY=0;
                                $tempY=$this->arraydetail[0]["y_axis"];
                            }
                            //check lastpagefooter existed or not
                            elseif(isset($this->arraylastPageFooter)&&(($checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt))))>($this->arrayPageSetting["pageHeight"]-$this->arraylastPageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]))) {

                                $this->showGroupFooter();
                                $this->lastPageFooter();
                             //   $this->pdf->AddPage();
                               // $this->background();
                                $this->pageHeaderNewPage();

                              //  $this->showGroupHeader();
                                $checkpoint=$this->arraydetail[0]["y_axis"];
                                $biggestY=0;
                                $tempY=$this->arraydetail[0]["y_axis"];
                            }

                            if(($checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt))))>$tempY) {
                                $tempY=$checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt)));
                            }
                            break;
                        case "relativebottomline":
                            break;
                        case "report_count":
//                            $this->report_count++;
                            break;
                               case "group_count":
//###                            $this->group_count++;
                            break;
                        default:
                            $this->display($compare,$checkpoint);

                            break;
                    }
                }



                if($checkpoint+$this->arraydetail[0]["height"]>($this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]))	//check the upcoming band is greater than footer position or not
                {
                    $this->pageFooter();

              //      $this->pdf->AddPage();
                //    $this->background();
                    $headerY = $this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];
                    $this->pageHeaderNewPage();
                  //  $this->showGroupHeader();

                    $checkpoint=$this->arraydetail[0]["y_axis"];
                    $biggestY=0;
                    $tempY=$this->arraydetail[0]["y_axis"];
                }

                foreach ($this->arraydetail as $out) {
                  $this->currentrow=$this->arraysqltable[$this->global_pointer];
                    switch ($out["hidden_type"]) {
                        case "field":

                            $this->prepare_print_array=array("type"=>"MultiCell","width"=>$out["width"],"height"=>$out["height"],"txt"=>$out["txt"],"border"=>$out["border"],"align"=>$out["align"],"fill"=>$out["fill"],"hidden_type"=>$out["hidden_type"],"printWhenExpression"=>$out["printWhenExpression"],"soverflow"=>$out["soverflow"],"poverflow"=>$out["poverflow"],"link"=>$out["link"],"pattern"=>$out["pattern"]);
                            $this->display($this->prepare_print_array,0,true);

                            if($this->pdf->GetY() > $biggestY) {
                                $biggestY = $this->pdf->GetY();
                            }
                            break;
                        case "relativebottomline":
                        //$this->relativebottomline($out,$tempY);
                            $this->relativebottomline($out,$biggestY);
                            break;
                        default:

                            $this->display($out,$checkpoint);

                            //$checkpoint=$this->pdf->GetY();
                            break;
                    }
                }
                $this->pdf->SetY($biggestY);
                if($biggestY>$checkpoint+$this->arraydetail[0]["height"]) {
                    $checkpoint=$biggestY;
                }
                elseif($biggestY<$checkpoint+$this->arraydetail[0]["height"]) {
                    $checkpoint=$checkpoint+$this->arraydetail[0]["height"];
                }
                else {
                    $checkpoint=$biggestY;
                }
if(isset($this->arraygroup)&&($this->global_pointer>0)&&($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer+1][$this->group_pointer]))
      $this->showGroupFooter($tempY);
                //if(isset($this->arraygroup)){$this->global_pointer++;}
                $this->global_pointer++;
            }
        }else {
            echo utf8_decode("Sorry cause there is not result from this query.");
            exit(0);
        }
        $this->global_pointer--;
                    $rownum++;
                       if($this->arraysummary[0]["height"]>0)
                    $this->summary();
        if(isset($this->arraylastPageFooter)) {
        //     $this->showGroupFooter();
            $this->lastPageFooter();
        }
        else {
        //     $this->showGroupFooter();
            $this->pageFooter();
        }


    }
*/
    public function showGroupHeader($y) {
        $this->currentband='groupHeader';
        $bandheight=$this->arraygrouphead[0]['height'];
	 
	    foreach ($this->arraygrouphead as $out) {
  		   $this->display($out,$y,!($out['hidden_type'] == 'statictext'));
           }
        $this->currentband='';
        return $bandheight;
    }
    public function showGroupFooter($y) {
        $this->currentband='groupFooter';
        //$this->pdf->MultiCell(100,10,"???1-$y,XY=". $this->pdf->GetX().",". $this->pdf->GetY());
        $bandheight=$this->arraygroupfoot[0]['height'];
	
        foreach ($this->arraygroupfoot as $out) {
			$this->display($out,$y,!$out['hidden_type'] == 'statictext');
        }
        $this->footershowed=true;
        $this->currentband='';
        return $bandheight;
        //$this->pdf->MultiCell(100,10,"???1-$y,XY=". $this->pdf->GetX().",". $this->pdf->GetY());

        $this->footershowed=true;
        return $bandheight;
        //$this->pdf->MultiCell(100,10,"???1-$y,XY=". $this->pdf->GetX().",". $this->pdf->GetY());

    }


    public function display($arraydata,$y_axis=0,$fielddata=false,$maxheight=0) {
  //print_r($arraydata);echo "<br/>";
    //    $this->pdf->Cell(10,10,"SSSS");
    $this->Rotate($arraydata["rotation"]);
    
    if($arraydata["rotation"]!=""){



    if($arraydata["rotation"]=="Left"){
         $w=$arraydata["width"];
        $arraydata["width"]=$arraydata["height"];
        $arraydata["height"]=$w;
            $this->pdf->SetXY($this->pdf->GetX()-$arraydata["width"],$this->pdf->GetY());
    }
    elseif($arraydata["rotation"]=="Right"){
         $w=$arraydata["width"];
        $arraydata["width"]=$arraydata["height"];
        $arraydata["height"]=$w;
            $this->pdf->SetXY($this->pdf->GetX(),$this->pdf->GetY()-$arraydata["height"]);
    }
    elseif($arraydata["rotation"]=="UpsideDown"){
        //soverflow"=>$stretchoverflow,"poverflow"
        $arraydata["soverflow"]=true;
        $arraydata["poverflow"]=true;
       //   $w=$arraydata["width"];
       // $arraydata["width"]=$arraydata["height"];
        //$arraydata["height"]=$w;
        $this->pdf->SetXY($this->pdf->GetX()- $arraydata["width"],$this->pdf->GetY()-$arraydata["height"]);
    }

    }

        if($arraydata["type"]=="SetFont") {
      //      if($arraydata["font"]=='uGB')
      //          $this->pdf->isUnicode=true;
      //      else
            // WE ONLY DO UTF8 - late maybe uGB
                $this->pdf->isUnicode=true;

            $this->pdf->SetFont($this->getFontMap($arraydata["font"]),$arraydata["fontstyle"],$arraydata["fontsize"]);

        }
        elseif($arraydata["type"]=="subreport") {
			
            $this->runSubReport($arraydata,$y_axis);
//            $y_axis=$this->SubReportCheckPoint;
        }
        elseif($arraydata["type"]=="MultiCell") {
           
            if($fielddata==false) {
                $this->checkoverflow($arraydata,$this->updatePageNo($arraydata["txt"]),'',$maxheight);
            }
            elseif($fielddata==true) {
                $this->checkoverflow($arraydata,$this->updatePageNo($this->analyse_expression($arraydata["txt"],$arraydata["isPrintRepeatedValues"] )),$maxheight);
            }
        }
        elseif($arraydata["type"]=="SetXY") {
            $this->pdf->SetXY($arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis);
        }
        elseif($arraydata["type"]=="Cell") {


            $this->pdf->Cell($arraydata["width"],$arraydata["height"],$this->updatePageNo($arraydata["txt"]),$arraydata["border"],$arraydata["ln"],$arraydata["align"],$arraydata["fill"],$arraydata["link"]);


        }
        elseif($arraydata["type"]=="Rect"){
		if($arraydata['mode']=='Transparent')
		$style='';
		else
		$style='FD';
		
			$this->pdf->Rect($arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,$arraydata["width"],$arraydata["height"],
			$style);
                }
        elseif($arraydata["type"]=="RoundedRect"){
			if($arraydata['mode']=='Transparent')
				$style='';
			else
			$style='FD';
			
			 $this->pdf->RoundedRect($arraydata["x"]+$this->arrayPageSetting["leftMargin"], $arraydata["y"]+$y_axis, $arraydata["width"],$arraydata["height"], $arraydata["radius"], '1111', 
			$style,array('color'=>$arraydata['drawcolor']),$arraydata['fillcolor']);
			}
        elseif($arraydata["type"]=="Ellipse"){
			 $this->pdf->Ellipse($arraydata["x"]+$arraydata["width"]/2+$this->arrayPageSetting["leftMargin"], $arraydata["y"]+$y_axis+$arraydata["height"]/2, $arraydata["width"]/2,$arraydata["height"]/2,
				0,0,360,'FD',array('color'=>$arraydata['drawcolor']),$arraydata['fillcolor']);
			}
        elseif($arraydata["type"]=="Image") {
            $path=$this->analyse_expression($arraydata["path"]);
// find image in root path			
				if (file_exists($this->report_path. $path))
			   $path =$this->report_path. $path;
                  
		 $imgtype=substr($path,-3);
            if($imgtype=='jpg')
				$imgtype="JPEG";
   //($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false) {
			      
        if(file_exists($path))
            $this->pdf->Image($path,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,$arraydata["width"],$arraydata["height"],$imgtype,$arraydata["link"],substr($arraydata["hAlign"],0,1), false, 300, '', false, false, 0, true, false);
        }

        elseif($arraydata["type"]=="SetTextColor") {
			$this->textcolor_r=$arraydata['r'];
			$this->textcolor_g=$arraydata['g'];
			$this->textcolor_b=$arraydata['b'];
			
			if($this->hideheader==true && $this->currentband=='pageHeader')
				$this->pdf->SetTextColor(100,33,30);
			else
				$this->pdf->SetTextColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
        }
        elseif($arraydata["type"]=="SetDrawColor") {
			$this->drawcolor_r=$arraydata['r'];
			$this->drawcolor_g=$arraydata['g'];
			$this->drawcolor_b=$arraydata['b'];
            $this->pdf->SetDrawColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
        }
        elseif($arraydata["type"]=="SetLineWidth") {
            $this->pdf->SetLineWidth($arraydata["width"]);
        }
        elseif($arraydata["type"]=="Line") {
            $this->pdf->Line($arraydata["x1"]+$this->arrayPageSetting["leftMargin"],$arraydata["y1"]+$y_axis,$arraydata["x2"]+$this->arrayPageSetting["leftMargin"],$arraydata["y2"]+$y_axis);
        }
        elseif($arraydata["type"]=="SetFillColor") {
			$this->fillcolor_r=$arraydata['r'];
			$this->fillcolor_g=$arraydata['g'];
			$this->fillcolor_b=$arraydata['b'];
            $this->pdf->SetFillColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
        }
      elseif($arraydata["type"]=="lineChart") {

            $this->showLineChart($arraydata, $y_axis);
        }
      elseif($arraydata["type"]=="barChart") {

            $this->showBarChart($arraydata, $y_axis,'barChart');
        }
      elseif($arraydata["type"]=="stackedBarChart") {

            $this->showBarChart($arraydata, $y_axis,'stackedBarChart');
        }
      elseif($arraydata["type"]=="stackedAreaChart") {

            $this->showAreaChart($arraydata, $y_axis,$arraydata["type"]);
        }

    }

    public function relativebottomline($path,$y) {
        $extra=$y-$path["y1"];
        $this->display($path,$extra);
    }

    public function updatePageNo($s) {
        return str_replace('$this->PageNo()', $this->pdf->PageNo(),$s);
    }

    public function staticText($xml_path) {//$this->pointer[]=array("type"=>"SetXY","x"=>$xml_path->reportElement["x"],"y"=>$xml_path->reportElement["y"]);
    }
    


    public function checkoverflow($arraydata,$txt="",$maxheight=0) {


        $this->print_expression($arraydata);

      
        if($this->print_expression_result==true) {

            if($arraydata["link"]) {
                $arraydata["link"]=$this->analyse_expression($arraydata["link"],"");

            }

            if($arraydata["writeHTML"]==1 && $this->pdflib=="TCPDF") {
                $this->pdf->writeHTML($txt);
			$this->pdf->Ln();
					if($this->currentband=='detail'){
					if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
						$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					else{
						if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
							$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					}
				}
            
            }
            elseif($arraydata["poverflow"]=="true"&&$arraydata["soverflow"]=="false") {
                
                $this->pdf->Cell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]),$arraydata["border"],"",$arraydata["align"],$arraydata["fill"],$arraydata["link"]);
				$this->pdf->Ln();
					if($this->currentband=='detail'){
					if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
						$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					else{
						if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
							$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					}
				}
            
            }
            elseif($arraydata["poverflow"]=="false"&&$arraydata["soverflow"]=="false") {
                while($this->pdf->GetStringWidth($txt) > $arraydata["width"]) {
                    $txt=substr_replace($txt,"",-1);
                }
                $this->pdf->Cell($arraydata["width"], $arraydata["height"],$this->formatText($txt, $arraydata["pattern"]),
						$arraydata["border"],"",$arraydata["align"],$arraydata["fill"],$arraydata["link"]);
				$this->pdf->Ln();
					if($this->currentband=='detail'){
					if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
						$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					else{
						if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
							$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					}
				}
		
            }
            elseif($arraydata["poverflow"]=="false"&&$arraydata["soverflow"]=="true") {
				
				$x=$this->pdf->GetX();
// calc height
				$calcHeight = $this->pdf->getStringHeight($arraydata["width"],$this->formatText($txt, $arraydata["pattern"]));
				$al = 'T';
				$thismax = $maxheight;
				
				if (empty($arraydata["valign"]))
				   $arraydata["valign"]= 'T';
				if ($arraydata["valign"] != 'T')
				 {
				if ($calcHeight < $arraydata["height"])
				    {
				   $al = $arraydata["valign"];
				   $thismax = $arraydata["height"];
				   }
				 } 
		
		        $this->pdf->MultiCell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]), $arraydata["border"] 
       							,$arraydata["align"], $arraydata["fill"],1,'','',true,0,false,true,$thismax,$al);
		
		
				if( $this->pdf->balancetext=='' && $this->currentband=='detail'){
					if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
						$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					else{
						if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
							$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					}
				}
				
			//$this->pageFooter();
            if($this->pdf->balancetext!='' ){
				$this->continuenextpageText=array('width'=>$arraydata["width"], 'height'=>$arraydata["height"], 'txt'=>$this->pdf->balancetext,
						'border'=>$arraydata["border"] ,'align'=>$arraydata["align"], "valign"=>$out["valign"],'fill'=>$arraydata["fill"],'ln'=>1,
							'x'=>$x,'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,'autopadding'=>true);
					$this->pdf->balancetext='';
					$this->forcetextcolor_b=$this->textcolor_b;
					$this->forcetextcolor_g=$this->textcolor_g;
					$this->forcetextcolor_r=$this->textcolor_r;
					$this->forcefillcolor_b=$this->fillcolor_b;
					$this->forcefillcolor_g=$this->fillcolor_g;
					$this->forcefillcolor_r=$this->fillcolor_r;
					if($this->continuenextpageText)
						$this->printlongtext($this->pdf->getFontFamily(),$this->pdf->getFontStyle(),$this->pdf->getFontSize());
					
					}          
				
					
         

            }
            else {
				//MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0) {	
                $this->pdf->MultiCell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]), $arraydata["border"], 
							$arraydata["align"], $arraydata["fill"],1,'','',true,0,true,true,$maxheight);
				if( $this->pdf->balancetext=='' && $this->currentband=='detail'){
					if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
						$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					else{
						if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
							$this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
					}
				}
            if($this->pdf->balancetext!=''){
				$this->continuenextpageText=array('width'=>$arraydata["width"], 'height'=>$arraydata["height"], 'txt'=>$this->pdf->balancetext,
						'border'=>$arraydata["border"] ,'align'=>$arraydata["align"], 'fill'=>$arraydata["fill"],'ln'=>1,
							'x'=>$x,'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,'autopadding'=>true);
					$this->pdf->balancetext='';
					$this->forcetextcolor_b=$this->textcolor_b;
					$this->forcetextcolor_g=$this->textcolor_g;
					$this->forcetextcolor_r=$this->textcolor_r;
					$this->forcefillcolor_b=$this->fillcolor_b;
					$this->forcefillcolor_g=$this->fillcolor_g;
					$this->forcefillcolor_r=$this->fillcolor_r;
					$this->gotTextOverPage=true;
					if($this->continuenextpageText)
						$this->printlongtext($this->pdf->getFontFamily(),$this->pdf->getFontStyle(),$this->pdf->getFontSize());
					
					}          



            }
        }
        $this->print_expression_result=false;
        


    }

    public function hex_code_color($value) {
        $r=hexdec(substr($value,1,2));
        $g=hexdec(substr($value,3,2));
        $b=hexdec(substr($value,5,2));
        return array("r"=>$r,"g"=>$g,"b"=>$b);
    }

    public function get_first_value($value) {
        return (substr($value,0,1));
    }

    function right($value, $count) {

        return substr($value, ($count*-1));

    }

    function left($string, $count) {
        return substr($string, 0, $count);
    }

    public function analyse_expression($data,$isPrintRepeatedValue="true") {
      
        if (($isPrintRepeatedValue) and (empty($data)))
            return true;
     //   return $data;
// the expressions have completly changed so that we can do true exprssions.
// use classes for each language   language="javascript" in the jrxml or language="groovy"
// javascript is workign
	//  if (empty($data))
	//     return null;
		 
	
	
  $lang = ucfirst($this->arrayPageSetting["language"]);
  if (empty($lang))
       $lang = 'Javascript';

	   $engineClass = 'jasper'.$lang;
        $expEngine = new $engineClass();

		foreach($this->arraysqltable[$this->global_pointer] as $name => $value)
		    $expEngine->addVar('$F{'.$name.'}' ,$value);
		foreach($this->arrayVariable as $name => $value)
		    $expEngine->addVar('$V{'.$name.'}' ,$value);
	
	foreach($this->arrayParameter as $name => $value)
		    $expEngine->addVar('$P{'.$name.'}' ,$value);
	
		$result = $expEngine->run($data);
		if ($result == 'false')
		   return false;
		if ($result == 'true')
		   return true;
		return $result;
                
                
     /// this below needs be put in a groovy class
	
    }

    public function formatText($txt,$pattern) {
        if($pattern=="###0")
            return number_format($txt,0,"","");
        elseif($pattern=="#,##0")
            return number_format($txt,0,".",",");
        elseif($pattern=="###0.0")
            return number_format($txt,1,".","");
        elseif($pattern=="#,##0.0")
            return number_format($txt,1,".",",");
        elseif($pattern=="###0.00")
            return number_format($txt,2,".","");
        elseif($pattern=="#,##0.00")
            return number_format($txt,2,".",",");
        elseif($pattern=="###0.000")
            return number_format($txt,3,".","");
        elseif($pattern=="#,##0.000")
            return number_format($txt,3,".",",");
        elseif($pattern=="#,##0.0000")
            return number_format($txt,4,".",",");
        elseif($pattern=="###0.0000")
            return number_format($txt,4,".","");
        elseif($pattern=="dd/MM/yyyy" && $txt !="")
            return date("d/m/Y",strtotime($txt));
        elseif($pattern=="MM/dd/yyyy" && $txt !="")
            return date("m/d/Y",strtotime($txt));
        elseif($pattern=="yyyy/MM/dd" && $txt !="")
            return date("Y/m/d",strtotime($txt));
        elseif($pattern=="dd-MMM-yy" && $txt !="")
            return date("d-M-Y",strtotime($txt));
        elseif($pattern=="dd-MMM-yy" && $txt !="")
            return date("d-M-Y",strtotime($txt));
        elseif($pattern=="dd/MM/yyyy h.mm a" && $txt !="")
            return date("d/m/Y h:i a",strtotime($txt));
        elseif($pattern=="dd/MM/yyyy HH.mm.ss" && $txt !="")
            return date("d-m-Y H:i:s",strtotime($txt));
        else
            return $txt;


    }

    public function print_expression($data) {
        $expression=$data["printWhenExpression"];
// use analyse_expression 
		if($expression=="") {
            $this->print_expression_result=true;
			return ;
			}
		$result = $this->analyse_expression($expression);
		$this->print_expression_result =$result ;
		return;
		
	   $expression=str_replace('$F{','$this->arraysqltable[$this->global_pointer][',$expression);
        $expression=str_replace('$P{','$this->arrayParameter[',$expression);
        $expression=str_replace('$V{','$this->arrayVariable[',$expression);
        $expression=str_replace('}',']',$expression);
	
        $this->print_expression_result=false;
	    if($expression!="") {
            eval('if('.$expression.'){$this->print_expression_result=true;}');
        }
        elseif($expression=="") {
            $this->print_expression_result=true;
        }
	
    }

    public function runSubReport($d,$current_y) {
            $this->insubReport=1;
        foreach($d["subreportparameterarray"] as $name=>$b) {
            $t = $b->subreportParameterExpression;
            $arrdata=explode("+",$t);
            $i=0;
            foreach($arrdata as $num=>$out) {
                $i++;
//                $arrdata[$num]=str_replace('"',"",$out);
                if(substr($b,0,3)=='$F{') {
                    $arrdata2[$name.'']=$this->arraysqltable[$this->global_pointer][substr($b,3,-1)];
                }
                elseif(substr($b,0,3)=='$V{') {
                    $arrdata2[$name.'']=&$this->arrayVariable[substr($b,3,-1)]["ans"];
                }
                elseif(substr($b,0,3)=='$P{') {
                    $arrdata2[$name.'']=$this->arrayParameter[substr($b,3,-1)];
                }
            }
            $t=implode($arrdata);
        }
   /*         if($this->currentband=='pageHeader'){
                $this->includeSubReport($d,$arrdata2,$current_y);
            }
            if($this->currentband=='pageFooter'){
                $this->includeSubReport($d,$arrdata2,$current_y);
            }
            if($this->currentband=='lastPageFooter'){
                $this->includeSubReport($d,$arrdata2,$current_y);
            }
            if($this->currentband=='groupHeader'){
                $this->includeSubReport($d,$arrdata2,$current_y);
            }
            if($this->currentband=='groupFooter'){
                $this->includeSubReport($d,$arrdata2,$current_y);
            }
            if($this->currentband=='summary'){
                $this->includeSubReport($d,$arrdata2,$current_y);
            }
            if($this->currentband=='detail'){
                $this->includeSubReport($d,$arrdata2,$current_y);
            }
       */
              $this->includeSubReport($d,$arrdata2,$current_y);
        $this->insubReport=0;
    }
    
    public function transferXMLtoArray($fileName) {
        if(!file_exists($fileName))
            echo "File - $fileName does not exist";
        else {

            $xmlAry = $this->xmlobj2arr(simplexml_load_file($fileName));
			
            foreach($xmlAry[header] as $key => $value)
                $this->arraysqltable["$this->m"]["$key"]=$value;

            foreach($xmlAry[detail][record]["$this->m"] as $key2 => $value2)
                $this->arraysqltable["$this->m"]["$key2"]=$value2;
        }

      //  if(isset($this->arrayVariable))	//if self define variable existing, go to do the calculation
       //     $this->variable_calculation();

    }

    public function includeSubReport($d,$arrdata,$current_y){ 
               include_once ("PHPJasperXMLSubReport.inc.php");
               $srxml=  simplexml_load_file($d['subreportExpression']);
               $PHPJasperXMLSubReport= new PHPJasperXMLSubReport($this->lang,$this->pdflib,$d['x']);
               $PHPJasperXMLSubReport->arrayParameter=$arrdata;
               $PHPJasperXMLSubReport->debugsql=1;
               $PHPJasperXMLSubReport->xml_dismantle($srxml);
               $this->passAllArrayDatatoSubReport($PHPJasperXMLSubReport,$d,$current_y);
               $PHPJasperXMLSubReport->setDatabase($this->_database);
               $PHPJasperXMLSubReport->pdf=$this->pdf;
               $PHPJasperXMLSubReport->outpage();    //page output method I:standard output  D:Download file
  
               $this->SubReportCheckPoint=$PHPJasperXMLSubReport->SubReportCheckPoint;
               $PHPJasperXMLSubReport->MainPageCurrentY=0;
    }

    public function passAllArrayDatatoSubReport($PHPJasperXMLSubReport,$d,$current_y){
        
                $PHPJasperXMLSubReport->arrayMainPageSetting=$this->arrayPageSetting;
                if(isset($this->arraypageHeader)) {
                $PHPJasperXMLSubReport->arrayPageSetting["subreportpageHeight"]=$PHPJasperXMLSubReport->arrayPageSetting["pageHeight"];
                $PHPJasperXMLSubReport->arrayMainpageHeader=$this->arraypageHeader;
                $PHPJasperXMLSubReport->arrayMainpageFooter=$this->arraypageFooter;

                    if($this->currentband=='pageHeader'){ ///here need to add more conditions to fulfill different band subreport
                        $PHPJasperXMLSubReport->TopHeightFromMainPage=$PHPJasperXMLSubReport->arrayMainPageSetting["topMargin"]+$d['y'];
                    }
                    else{      
                        $PHPJasperXMLSubReport->TopHeightFromMainPage=$PHPJasperXMLSubReport->arrayMainPageSetting["topMargin"]
                                                                                                +$PHPJasperXMLSubReport->arrayMainpageHeader[0]["height"]+$d['y'];
                    }
###set different initial Y for subreport of each detail loop of main report
                if($current_y>$PHPJasperXMLSubReport->TopHeightFromMainPage){$PHPJasperXMLSubReport->TopHeightFromMainPage=$current_y+$d['y'];}
###
                $PHPJasperXMLSubReport->BottomHeightFromMainPage=$PHPJasperXMLSubReport->arrayMainPageSetting["bottomMargin"]
                                                                                                +$PHPJasperXMLSubReport->arrayMainpageFooter[0]["height"];
                $PHPJasperXMLSubReport->arrayPageSetting["leftMargin"]=$PHPJasperXMLSubReport->arrayPageSetting["leftMargin"]+$this->arrayPageSetting["leftMargin"];
###Set fixed pageHeight constant despite the changes of $PHPJasperXMLSubReport->TopHeightFromMainPage due to subreport in Detail band
                $PHPJasperXMLSubReport->arrayPageSetting["pageHeight"]=$this->arrayPageSetting["pageHeight"]
                                                                                                                    -($PHPJasperXMLSubReport->arrayMainPageSetting["topMargin"]
                                                                                                +$PHPJasperXMLSubReport->arrayMainpageHeader[0]["height"]+$d['y'])
                                                                                                                    -$this->arraypageFooter[0]["height"]
                                                                                                                    -$PHPJasperXMLSubReport->arrayMainPageSetting["bottomMargin"]-$d['y'];
###
//                $PHPJasperXMLSubReport->arrayPageSetting["pageHeight"]=$this->arrayPageSetting["pageHeight"]
//                                                                                                                    -$PHPJasperXMLSubReport->TopHeightFromMainPage
//                                                                                                                    -$this->arraypageFooter[0]["height"]
//                                                                                                                    -$PHPJasperXMLSubReport->arrayMainPageSetting["bottomMargin"]-$d['y'];
//                    $PHPJasperXMLSubReport->arrayPageSetting['topMargin']=$PHPJasperXMLSubReport->arrayPageSetting['topMargin']
//                                                                                                                    +$PHPJasperXMLSubReport->arrayMainPageSetting["topMargin"]
//                                                                                                                    +$PHPJasperXMLSubReport->arrayMainpageHeader[0]["height"];

//                    elseif($this->currentband=='detail'){
////                        $PHPJasperXMLSubReport->MainPageCurrentY=$current_y;
//                    }
                }
                if(isset($this->arraypageFooter)) {
                    $PHPJasperXMLSubReport->arrayMainpageFooter=$this->arraypageFooter;
                }
                if(isset($this->arraygroup)) {
                    $PHPJasperXMLSubReport->arrayMaingroup=$this->arraygroup;
                }
                if(isset($this->arraylastPageFooter)) {
                    $PHPJasperXMLSubReport->arrayMainlastPageFooter=$this->arraylastPageFooter;
                }
                if(isset($this->arraytitle)) {
                    $PHPJasperXMLSubReport->arrayMaintitle=$this->arraytitle;
                }

    }
//wrote by huzursuz at mailinator dot com on 02-Feb-2009 04:44
//http://hk.php.net/manual/en/function.get-object-vars.php
    public function xmlobj2arr($Data) {
        if (is_object($Data)) {
            foreach (get_object_vars($Data) as $key => $val)
                $ret[$key] = $this->xmlobj2arr($val);
            return $ret;
        }
        elseif (is_array($Data)) {
            foreach ($Data as $key => $val)
                $ret[$key] = $this->xmlobj2arr($val);
            return $ret;
        }
        else
            return $Data;
    }


private function Rotate($type, $x=-1, $y=-1)
{
    if($type=="")
    $angle=0;
    elseif($type=="Left")
    $angle=90;
    elseif($type=="Right")
    $angle=270;
    elseif($type=="UpsideDown")
    $angle=180;

    if($x==-1)
        $x=$this->pdf->getX();
    if($y==-1)
        $y=$this->pdf->getY();
    if($this->angle!=0)
        $this->pdf->_out('Q');
    $this->angle=$angle;
    if($angle!=0)
    {
        $angle*=M_PI/180;
        $c=cos($angle);
        $s=sin($angle);
        $cx=$x*$this->pdf->k;
        $cy=($this->pdf->h-$y)*$this->pdf->k;
        $this->pdf->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
    }
}

}
