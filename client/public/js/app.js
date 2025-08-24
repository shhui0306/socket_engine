// Place third party dependencies in the lib folder
//
// Configure loading modules from the lib directory,
// except 'app' ones, 
requirejs.config({
    "baseUrl": "../js/lib",
    "paths": {
      'jquery': '//code.jquery.com/jquery-3.5.1.min',
      'socket': '//cdnjs.cloudflare.com/ajax/libs/socket.io/1.3.7/socket.io.min',
      "app": "../app",
    //   "jquery": "//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min"
    }
});

// Load the main app module to start the app
requirejs(["app/main"]);