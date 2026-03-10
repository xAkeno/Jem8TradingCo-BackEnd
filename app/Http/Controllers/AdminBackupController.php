<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\admin_backup;
use Illuminate\Auth\Events\Validated;

use function Symfony\Component\Clock\now;

class AdminBackupController extends Controller
{
    //post new backup
    public function adminRunBackup(Request $request){
        
        $request->validate([
            'backup_type' => 'required|intenger|in:1,2,3',
        ]);

        $backup = admin_backup::create([
            'backup_type'=> $request->backup_type,
            'backup_size'=> 0,
            'status'=> 'pending',
            'file_name'=> 'null',
            'backup_patch'=> 'null',
        ]);
        
        $fileName = 'backup_' .$backup->backup_id . '_'. now()->format('Ymd_His') . '.sql';
        $backupPath ='backups/' . $fileName;

        if ($request->backup_type == 1){

        }elseif ($request->backup_type == 2){

        }elseif ($request->backup_type == 3){

        }

        $backup->update([
            'status' => 'completed',
            'file_name' => $fileName,
            'backup_path' => $backupPath,
            'backup_size' =>0,
        ]);
    }
    
    //get backup
    public function adminHistoryBackup(){

    }

    //get dowload backup
    public function adminDownloadBackup($id){

    }

    public function adminDeleteBackup($id){

    }

    public function adminUploadRestore(Request $request){

    }

}
