//  Javascript file to create image popup div when hover action is performed

var tstate = false;
var tstatethumb = false;
var popupoffsetXfromcursor = 20; 
var popupoffsetYfromcursor = 20;
var popupdivheight = 200;
var popupdivpadding = 10;
var divpopup = document.createElement("div");
divpopup.style.position = "fixed";
divpopup.style.height = popupdivheight + "px";
divpopup.style.width = "auto";
divpopup.setAttribute("align", "center");
divpopup.style.display = "none";
divpopup.style.zIndex = 100;
divpopup.style.backgroundColor = "#FFFFFF";
divpopup.style.border = "3px solid black";
divpopup.style.padding = popupdivpadding + "px";

function magnifypic(filenm, sourcethumb, event) { //  Hover over image thumbnal to display pic popup
    if (divpopup.firstChild === null) {  //  Test to make sure divpopup is empty before adding an image
        document.getElementsByTagName("body")[0].appendChild(divpopup);
        var domImg = new Image();
        domImg.src = filenm;
        domImg.alt = "No Image";
        domImg.height = popupdivheight;
        divpopup.appendChild(domImg);
        domImg.onload = function() { //  Wait for image to load so we can calcualte width from set height
            var xOff = event.clientX;
            var yOff = event.clientY;
            calculatedimagewidth = domImg.naturalWidth * popupdivheight / domImg.naturalHeight;
            xOff += ((xOff < window.innerWidth/2) ? popupoffsetXfromcursor : (-1 * (calculatedimagewidth + 2 * popupoffsetXfromcursor)));
            yOff += ((yOff < window.innerHeight/2) ? popupoffsetYfromcursor : (-1 * (popupdivheight + 2 * popupoffsetYfromcursor)));
            divpopup.style.left = xOff;
            divpopup.style.top = yOff;
            divpopup.style.display = "block";
        };
    }
}

function demagnifypic() {  //  Remove pic popup
    divpopup.style.display = "none";
    while (divpopup.firstChild) divpopup.removeChild(divpopup.firstChild);  //  Empty all images from divpopup
}