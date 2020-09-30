# Laravel Toolbox

Package to Toolbox for easy development with Laravel

# Installation

    composer require shortcodes/toolbox
    
# Requirements

Package requires Laravel version >= 8

# Description

Package contains traits and classes that improve speed and allows rapid development

## Cruddable

Trait allow to implement CRUD right away

    use Shortcodes\Toolbox\Traits\Crudable;
    
    class ExampleController extends Controller
    {
        use Crudable;
    }

Using this trait some properties should be defined:

    ...
    
    //mandatory
    protected $model; // eloquent model
    
    //optional
    protected $objectResource; // resource for singular object
    
    protected $listResource; // resource for collection
    
    protected $pagination; // should results be paginated
    
    protected $requests = [
        'show' => // request class applied to show 
        'index' => // request class applied to index 
        'store' => // request class applied to store 
        'update' => // request class applied to update 
        'destroy' => // request class applied to delete 
    ]; 

