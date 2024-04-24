<?php

namespace App\Console\Commands;
use App\Models\Task;
use Illuminate\Console\Command;
use App\Models\Users;
use App\Models\UserTargetsModel;

class CustomTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'custom:task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        
        

        $year = date('Y');   
        
        $month = date('m', strtotime(date('d-m-Y') . ' -1 month'));
        $userTargets = UserTargetsModel::where('month', $month)->where('year',$year)->get();
        
        if($userTargets !== null){
            foreach($userTargets as $target){
                $checkTarget = UserTargetsModel::where('user_id',$target->user_id)->where('unit_id', $target->unit_id)->where('month', date('n'))->where('year', date('Y'))->first();
                if($checkTarget == null){
                    $addTarget = new UserTargetsModel();
                    $addTarget->user_id = $target->user_id;
                    $addTarget->unit_id = $target->unit_id;
                    $addTarget->year = $target->year;
                    $addTarget->month = date('n');
                    $addTarget->target = $target->target;
                    $addTarget->status = 1;
                    $addTarget->save();
                }

            }      
        }
        

        
        
        
    }
}
