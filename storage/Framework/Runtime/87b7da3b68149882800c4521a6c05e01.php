<?php



namespace Watish\WatishWEB\Command;



use Watish\Components\Attribute\Command;

use Watish\Components\Constructor\LocalFilesystemConstructor;

use Watish\Components\Constructor\ValidatorConstructor;

use Watish\Components\Utils\Logger;



#[Command('model',"make")]

class PROXY_97f9c1672803576_MakeModelCommand implements CommandInterface

{

    public function handle(): void

    {

        $climate = Logger::CLImate();

        $classNameInput = $climate->input("Class Name:");

        $class_name = $classNameInput->prompt();

        $tableNameInput = $climate->input("Table Name:");

        $table_name = $tableNameInput->prompt();

        $primaryKeyInput = $climate->input("Primary Key:");

        $primary_key = $primaryKeyInput->prompt();

        $validator = ValidatorConstructor::make([

            "class_name" => $class_name,

            "table_name" => $table_name,

            "primary_key" => $primary_key

        ],

        [

            "class_name" => 'required|string',

            "table_name" => 'required|string',

            "primary_key" => 'required|string'

        ]);

        if($validator->fails())

        {

            Logger::error("Input Error");

            return;

        }

        $file_system = LocalFilesystemConstructor::getFilesystem();

        $file_path = '/src/Model/'.$class_name.'.php';

        if($file_system->fileExists($file_path))

        {

            Logger::error($class_name.' File Exists');

            return;

        }



        $code =

'<?php



namespace Watish\WatishWEB\Model;



use Illuminate\Database\Eloquent\Model;



class {class_name} extends Model

{

   protected $table = \'{table_name}\';

   protected $primaryKey = \'{primary_key}\';



}';

        $code = str_replace('{class_name}',$class_name,$code);

        $code = str_replace('{table_name}',$table_name,$code);

        $code = str_replace('{primary_key}',$primary_key,$code);



        try{

            $file_system->write($file_path,$code);

        }catch (\Exception $exception)

        {

            Logger::exception($exception);

        }

        Logger::info("Model $class_name Created Successfully");

    }



}

