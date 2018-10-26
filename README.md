<h1>Single-File-PHP-file-manager</h1>

The inspiration for this project was my inability to use an FTP client remotely when working on CS projects that required I save my work to a campus-hosted server. I built a really terrible solution at the time which did some really janky things and was mostly contained in one enormous echo block, but I recently revisited it and rewrote it from the ground up. 

The biggest challenge was keeping everything in one file while maintaining a decent feature set and preserving readability. The latter was the real challenge. By and large things are well commented, though, I did have to use section comments which I'm not crazy about. There's some cases where things are shoved into one line, but only when it's self explanatory when the formatting doesn't look terrible.

<h2>Features</h2>
<ul>
  <li>View file list (with FontAwesome icons!)</li>
  <li>Filter results in file list</li>
  <li>Copy/delete/rename/preview/change permissions</li>
  <li>Drag and drop file movement between directories</li>
  <li>Create new files/folder</li>
  <li>file uploading</li>
  <li>AJAX (not more broken refresh/back buttons</li>
  <li>Simple login/'security'</li>
  <li>It's all in one PHP file!</li>
</ul>

<h2>Known bugs</h2>
<ul>
  <li>Dragging and dropping onto action buttons produces an error</li>
  <li>Top directory dragging/dropping doesn't work</li>
</ul>

<h2>Coming soon</h2>
<ul>
  <li>Better error handling</li>
  <li>Clean everything</li>
</ul>

<h2>Credits</h2>
This project makes use of Bootstrap, jQuery and FontAwesome, as well as a few functions which were created by users on PHP.net and Stack Overflow--These are credited in the source.

<h3>Note:</h3>
<strong>This has not been extensively tested! I am not liable for lost data! Use at your own risk!</strong>
