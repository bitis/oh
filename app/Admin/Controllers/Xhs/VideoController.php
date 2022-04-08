<?php

namespace App\Admin\Controllers\Xhs;

use App\Models\XhsVideo;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VideoController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'XhsVideo';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new XhsVideo());

        $grid->column('ID', __('ID'));
        $grid->column('XSH_NOTE_ID', __('XSH NOTE ID'));
        $grid->column('X_ID', __('X ID'));
        $grid->column('HEIGHT', __('HEIGHT'));
        $grid->column('WIDTH', __('WIDTH'));
        $grid->column('URL', __('URL'));
        $grid->column('CREATED_AT', __('CREATED AT'));
        $grid->column('UPDATED_AT', __('UPDATED AT'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(XhsVideo::findOrFail($id));

        $show->field('ID', __('ID'));
        $show->field('XSH_NOTE_ID', __('XSH NOTE ID'));
        $show->field('X_ID', __('X ID'));
        $show->field('HEIGHT', __('HEIGHT'));
        $show->field('WIDTH', __('WIDTH'));
        $show->field('URL', __('URL'));
        $show->field('CREATED_AT', __('CREATED AT'));
        $show->field('UPDATED_AT', __('UPDATED AT'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new XhsVideo());

        $form->number('ID', __('ID'));
        $form->number('XSH_NOTE_ID', __('XSH NOTE ID'));
        $form->text('X_ID', __('X ID'));
        $form->number('HEIGHT', __('HEIGHT'));
        $form->number('WIDTH', __('WIDTH'));
        $form->url('URL', __('URL'));
        $form->text('CREATED_AT', __('CREATED AT'));
        $form->text('UPDATED_AT', __('UPDATED AT'));

        return $form;
    }
}
