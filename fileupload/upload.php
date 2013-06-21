<html>
	<head><title>Upload</title></head>
<body>



<?php
if ( 0 )
{

require("fileupload-class.php");

#--------------------------------#
# Variables
#--------------------------------#

// The path to the directory where you want the 
// uploaded files to be saved. This MUST end with a 
// trailing slash unless you use $path = ""; to 
// upload to the current directory. Whatever directory
// you choose, please chmod 777 that directory.

	$path = "uploads/";

// The name of the file field in your form.

	$upload_file_name = "userfile";

// ACCEPT mode - if you only want to accept
// a certain type of file.
// possible file types that PHP recognizes includes:
//
// OPTIONS INCLUDE:
//  text/plain
//  image/gif
//  image/jpeg
//  image/png
	
	// Accept ONLY gifs's
	#$acceptable_file_types = "image/gifs";
	
	// Accept GIF and JPEG files
	#$acceptable_file_types = "image/gif|image/jpeg|image/pjpeg";

	// Accept ALL files
	#$acceptable_file_types = "";

// If no extension is supplied, and the browser or PHP
// can not figure out what type of file it is, you can
// add a default extension - like ".jpg" or ".txt"

	$default_extension = "";

// MODE: if your are attempting to upload
// a file with the same name as another file in the
// $path directory
//
// OPTIONS:
//   1 = overwrite mode
//   2 = create new with incremental extention
//   3 = do nothing if exists, highest protection

	$mode = 2;


#--------------------------------#
# PHP
#--------------------------------#
	if (isset($_REQUEST['submitted'])) {
		// Create a new instance of the class
		$my_uploader = new uploader;
		
		// OPTIONAL: set the max filesize of uploadable files in bytes
		$my_uploader->max_filesize(10000);
		
		// OPTIONAL: if you're uploading images, you can set the max pixel dimensions 
		$my_uploader->max_image_size(400, 600); /* max_image_size($width, $height) */
		
		// UPLOAD the file
		
		/* upload(file name[, acceptable mime types[, default extenstion]]) */
		if ($my_uploader->upload($upload_file_name, $acceptable_file_types, $default_extension)) {
			
			/* save_file(path[, mode]) */
			$success = $my_uploader->save_file($path, $mode);
		}
		
		if ($success) {
			// Successful upload!
			print($my_uploader->file['name'] . " was successfully uploaded! <a href=\"" . $_SERVER['PHP_SELF'] . "\">Try Again</a><br>");
			
			// Print all the array details...
			//print_r($my_uploader->file);
			
			
			// ...or print the file
			if ($path[strlen($path)-1] != "/") {
				$path = $path . "/";
			}
			if(ereg("image", $my_uploader->file['type'])) {
				echo "<img src=\"" . $path . $my_uploader->file['name'] . "\" border=\"0\" alt=\"\">";
			} else {
				$fp = fopen($path . $my_uploader->file['name'], "r");
				while(!feof($fp)) {
					$line = fgets($fp, 255);
					echo $line;
				}
				if ($fp) { fclose($fp); }
			}
			
	
		} else {
			// ERROR uploading...
 			if($my_uploader->errors) {
				while(list($key, $var) = each($my_uploader->errors)) {
					echo $var . "<br>";
				}
 			}
 		}
 	}




#--------------------------------#
# HTML FORM
#--------------------------------#
?>
	<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
	<input type="hidden" name="submitted" value="true">
		Upload this file:<br>
		<input name="<?php echo $upload_file_name; ?>" type="file">
		<br><br>
		<input type="submit" value="Send File">
	</form>
	<hr>

<?php
	if (isset($acceptable_file_types) && trim($acceptable_file_types)) {
		print("This form only accepts <b>" . str_replace("|", " or ", $acceptable_file_types) . "</b> files\n");
	}
?>



</body>
</html>

<?php
	}
?>
