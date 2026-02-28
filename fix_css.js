const fs = require('fs');
const path = 'c:\\xampp\\htdocs\\wptest\\wp-content\\plugins\\coderembassy-express-checkout\\assets\\css\\frontend.css';
let content = fs.readFileSync(path, 'utf8');

// Fix common corruption patterns
content = content.replace(/ - /g, '-');
content = content.replace(/ : /g, ': ');
content = content.replace(/ px/g, 'px');
content = content.replace(/ %/g, '%');
content = content.replace(/ fr/g, 'fr');
content = content.replace(/ ! important/g, '!important');
content = content.replace(/ \. /g, '.');
content = content.replace(/ {/g, ' {');
content = content.replace(/ ;/g, ';');
content = content.replace(/ }/g, '}');
content = content.replace(/ \( /g, '(');
content = content.replace(/ \) /g, ')');
content = content.replace(/ \/ /g, '/');
content = content.replace(/ , /g, ', ');
content = content.replace(/ @ keyframes/g, '@keyframes');
content = content.replace(/ ms/g, 'ms');
content = content.replace(/ s /g, 's ');
// wait, be careful with 's' inside words.
// Better for s:
content = content.replace(/(\d) s /g, '$1s ');

// Fix some specific broken selectors
content = content.replace(/\.coderembassy-custom-layout\.(woocommerce-billing-fields|woocommerce-shipping-fields|woocommerce-additional-fields|woocommerce-checkout-review-order|form-row|col2-set)/g, '.coderembassy-custom-layout .$1');

fs.writeFileSync(path, content);
console.log('Fixed CSS corruption');
