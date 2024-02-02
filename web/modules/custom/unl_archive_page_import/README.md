# Drupal 8 batch import example with interaction
![alt text](https://github.com/stef-van-looveren/batch-import-example/blob/master/docs/batch_example.gif?raw=true "Drupal 8 batch import example")

This example module will import 3 dogs from a json file as nodes:
```
[  
   {  
      "name":"Whiskers"
   },
   {  
      "name":"Woof"
   },
   {  
      "name":"Fluffy"
   }
]
```

## Batch processing
The form will create a batch process, which runs a small function to create the nodes. Batch processing is important when running imports with higher volumes to prevent the script to timeout.

## How to
1. Enable the module
2. Clear caches
3. Go to `admin/batch-import-example` and submit the form
4. A batch process will start and give interaction about which dog he is importing.

## Blog
For a blog on the usage of this module: [Create a batch process with interaction in drupal 8](https://stefvanlooveren.me/blog/create-batch-process-interaction-drupal-8-solved).
## Thanks
This module was built while working for [VITO (Flanders Institue for Technological Research)](https://www.vito.be).