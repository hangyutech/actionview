<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Project\Eloquent\Linked;

use DB;

class LinkController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $project_key)
    {
        $values = [];
        $src = $request->input('src');
        if (!$src)
        {
            throw new \UnexpectedValueException('the src issue value can not be empty.', -10002);
        }
        $values['src'] = $src;

        $relations = ['blocks', 'is blocked by', 'clones', 'is cloned by', 'duplicates', 'is duplicated by', 'relates to'];
        $relation = $request->input('relation');
        if (!$relation || !in_array($relation, $relations)) {
            throw new \UnexpectedValueException('the relation value has error.', -10002);
        }
        $values['relation'] = $relation;

        $dest = $request->input('dest');
        if (!$dest)
        {
            throw new \UnexpectedValueException('the dest issue value can not be empty.', -10002);
        }
        $values['dest'] = $dest;

        $values['creator'] = [ 'id' => $this->user->id, 'name' => $this->user->first_name ];

        $isExists = Linked::whereRaw([ 'src' => $src, 'dest' => $dest ])->exists();
        if ($isExists || Linked::whereRaw([ 'dest' => $src, 'src' => $dest ])->exists())
        {
            throw new \UnexpectedValueException('the relation of two issues has been exists.', -10002);
        }

        $ret = Linked::create($values);

        $link = [];
        $link['id'] = $ret->id;
        $link['relation'] = $ret->relation;

        $src_issue = DB::collection('issue_' . $project_key)->where('_id', $src)->first();
        $link['src'] = array_only($src_issue, ['_id', 'no', 'type', 'title', 'state']);

        $dest_issue = DB::collection('issue_' . $project_key)->where('_id', $dest)->first();
        $link['dest'] = array_only($dest_issue, ['_id', 'no', 'type', 'title', 'state']); 

        return Response()->json([ 'ecode' => 0, 'data' => parent::arrange($link) ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($project_key, $id)
    {
        $link = Linked::find($id);
        if (!$link)
        {
            throw new \UnexpectedValueException('the link does not exist or is not in the project.', -10002);
        }

        Linked::destroy($id);
        return Response()->json(['ecode' => 0, 'data' => ['id' => $id]]);
    }
}