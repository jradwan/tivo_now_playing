
imagePath = "images/"; // Default image path JIC.
function init()
{
     //console.log("document.domain = " + document.domain);
     //console.log("document.URL = " + document.URL);
     //console.log("path = " + document.getElementById("imagepath"));

     var imgpath = document.getElementById("imagepath");
     var domain = document.domain;
     console.info("domain=>",domain,"<");
     console.info("documentElement=>",document.documentElement,"<");
     //console.log("org imagePath =" + imagePath);
     if(imgpath != null){
         var str=imgpath.innerHTML;
         imagePath = myParse(str);
         // console.log("new imagePath = >>" + imagePath + "<<");
         // imagePath = "/tnpl/images/";
     }



    // Get the name cookie.value from the title that supports more then one TiVo
     if(document.title == '') document.title = "collapse_obj"; //FWTGBITN

     var cookie = getCookie(document.title);

     if(cookie)
     {
     	 var values = new Array();
         values = cookie.split(',');

         for(var i = 0; i < values.length; i++)
         {
         	 showHide(values[i]);
         }
     }
} // init()

/*
	getElementById("imagepath") returns a string with extra spaces and embeded quotes
        this function is to remove them.
*/

function myParse(mystr){
    // console.log("received mystr = >>"+mystr+"<<");
    var returnval = mystr.replace(/"/g,'');
    return new String(returnval.replace(/ /g,''));
}


function makeCookie(name, value)
{
     var cookie = name + '=' + escape(value) + ';';
     document.cookie = cookie;
}

function getCookie(name)
{
     if(document.cookie == '')
         return false;

     var firstPos;
     var lastPos;
     var cookie = document.cookie;

     firstPos = cookie.indexOf(name);

     if(firstPos != -1)
     {
         firstPos += name.length + 1;
         lastPos = cookie.indexOf(';', firstPos);

         if(lastPos == -1)
             lastPos = cookie.length;

         return unescape(cookie.substring(firstPos, lastPos));
     }

     else
         return false;
}

function getItem(id)
{
     var itm = false;
     if(document.getElementById)
         itm = document.getElementById(id);
     else if(document.all)
         itm = document.all[id];
     else if(document.layers)
         itm = document.layers[id];

     return itm;
}

function showHide(num)
{
     var strCollapseObjectName  = new String('myTbody' + num);
     var strPlusMinusObjectName  = new String('plusminus' + num);
     var objToggleItem          = null;
     var objPlusMinusImage      = null;
// var imagePath = "/tnpl/images/";

     //*****************************
     var gfxicons = false; //true or false
     //*****************************
	 	
     objToggleItem = getItem(strCollapseObjectName);
     objPlusMinusImage = getItem(strPlusMinusObjectName);

     if(!objToggleItem)
         return false;

   // if(objToggleItem.style.display == 'none') // breaks toggles in FireFox
   if(objToggleItem.style.display == '' || objToggleItem.style.display == 'none')
     {
         objToggleItem.style.display = 'block';
         if(gfxicons)
            objPlusMinusImage.src = imagePath + "tivo_show.gif";
         else
            objPlusMinusImage.src = imagePath + "minus.gif";
     }

     else
     {
         objToggleItem.style.display = 'none';
         if(gfxicons)
            objPlusMinusImage.src = imagePath + "tivo_hide.gif";
         else
            objPlusMinusImage.src = imagePath + "plus.gif";
     }
   return true;
}

/*
 * Expand or Collaps all 
 * Needed to expose all data for search
 *  
 * num initeger starting id 
 *  
 * Does not change the state of the cookie
 *  
 */
function toggleAll(num){
	var itm=num;
	while(showHide(itm++) && itm < 10000); // Set a upper linit for now 
	//console.info("num=", num, " item=",itm);	
}

function expandAll(num){
	
}


function toggleItem(num)
{
     showHide(num);

     cookie = getCookie(document.title);
     values = new Array();
     newval = new Array();
     add    = 1;

     if(cookie)
     {
         values = cookie.split(',');

         for(var i = 0; i < values.length; i++)
         {
             if(values[i] == num)
                 add = 0;
             else
                 newval[newval.length] = values[i];	     
         }
     }

     if(add)
         newval[newval.length] = num;

     makeCookie(document.title, newval.join(','));

     return false;
}
