<li>
  <span class="vendor">[[+vendor]]</span> &ndash; <span class="title">[[+title]]</span>
  <div class="description">
    [[+body_html]]
  </div>
  [[+images:notempty=`<div class="images">[[+images]]</div>`]]
  
  <a href="[[+multipassBuyNow]]" title="[[+title]]">Buy Now</a>
  
  <form action="[[+$multipassCart]]" method="post" target="_blank">
    <select name="id">[[+variants]]</select>
    <input type="submit" name="add" value="Buy" />
  </form>
</li>

<!--https://[[+domain]]/cart/add-->