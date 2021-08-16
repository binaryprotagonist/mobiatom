<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\OrganisationTheme;
use App\Model\Theme;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $theme = Theme::select('id', 'uuid', 'name', 'status')->orderBy('id', 'desc')->get();

        foreach ($theme as $key => $temp) {
            $org_theme = OrganisationTheme::where('theme_id', $temp->id)->first();
            if (is_object($org_theme)) {
                $theme[$key]->selected_theme = 1;
            }
        }

        $theme_array = array();
        if (is_object($theme)) {
            foreach ($theme as $key => $theme1) {
                $theme_array[] = $theme[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($theme_array[$offset])) {
                    $data_array[] = $theme_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($theme_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($theme_array);
        } else {
            $data_array = $theme_array;
        }

        return prepareResult(true, $data_array, [], "Template listing", $this->success, $pagination);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "change");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating theme", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            OrganisationTheme::where('organisation_id', $request->user()->organisation_id)->delete();

            $organisation_theme = new OrganisationTheme;
            $organisation_theme->theme_id = $request->theme_id;
            $organisation_theme->save();

            \DB::commit();
            return prepareResult(true, $organisation_theme, [], "Theme change successfully", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "change") {
            $validator = \Validator::make($input, [
                'theme_id' => 'required|integer|exists:themes,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
