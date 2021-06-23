/**
  Ape - local rules
  
  Rename this file to ape-local.js to use it.
  
  You can configure custom rules that replace the URL address 
  of a resource link with the address of the inline frame
  of some embedding service.

  See other examples defined as the "rx" array in ape.js.

  Custom rules override default ones: a link matching a custom 
  pattern will use the custom rule for the replacement rather 
  than a matching default one.

  Writing rules with regular expressions may be tricky: if you 
  have any difficulty do not hesitate to contact us.
*/

var uAPErx = uAPErx || [ ];

// uAPErx.push([ /pattern/, 'replacement' ]);
