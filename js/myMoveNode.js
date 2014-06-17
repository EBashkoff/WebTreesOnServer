function movenode(fr, to) {
    var sourcenode = document.getElementById(fr);
    var targetnode = document.getElementById(to);
    var temp = sourcenode.parentNode;
    temp = temp.removeChild(sourcenode);
    targetnode.appendChild(temp);
}