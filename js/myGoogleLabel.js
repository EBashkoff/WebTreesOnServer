// Below code adds label to marker taken from Marc Ridley's Blog at http://blog.mridey.com/2009/09/label-overlay-example-for-google-maps.html

// Define the overlay, derived from google.maps.OverlayView
function Label(opt_options, elevate) {
 // Initialization
 this.setValues(opt_options);
 var elevateamount = ((elevate==='small') ? -33 : -40); 
 // Label specific
 var span = this.span_ = document.createElement('span');
 span.style.cssText = 'position: relative; left: -50%; top: ' + elevateamount + 'px; ' +
                      'white-space: nowrap; font-size: 125%; ' +
                      'padding: 2px; background-color: transparent';

 var div = this.div_ = document.createElement('div');
 div.appendChild(span);
 div.style.cssText = 'position: absolute; display: none';
};
Label.prototype = new google.maps.OverlayView;

// Implement onAdd
Label.prototype.onAdd = function() {
 var pane = this.getPanes().overlayImage;  // Must be "overlayImage," NOT "overlayLayer"
 pane.appendChild(this.div_);

 // Ensures the label is redrawn if the text or position is changed.
 var me = this;
 this.listeners_ = [
   google.maps.event.addListener(this, 'position_changed',
       function() { me.draw(); }),
   google.maps.event.addListener(this, 'text_changed',
       function() { me.draw(); }),
   google.maps.event.addListener(this, 'zindex_changed',
       function() { me.draw(); })
 ];
};

// Implement onRemove
Label.prototype.onRemove = function() {
 this.div_.parentNode.removeChild(this.div_);

 // Label is removed from the map, stop updating its position/text.
 for (var i = 0, I = this.listeners_.length; i < I; ++i) {
   google.maps.event.removeListener(this.listeners_[i]);
 }
};

// Implement draw
Label.prototype.draw = function() {
 var projection = this.getProjection();
 var position = projection.fromLatLngToDivPixel(this.get('position'));

 var div = this.div_;
 div.style.left = position.x + 'px';
 div.style.top = position.y + 'px';
 div.style.display = 'block';
 div.style.zIndex = this.get('zindex') + 1;
 this.span_.innerHTML = this.get('text').toString();
};