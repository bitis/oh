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

        $grid->column('id', __('ID'));
        $grid->column('xsh_note_id', __('XSH NOTE ID'));
        $grid->column('x_id', __('X ID'));
        $grid->column('height', __('HEIGHT'));
        $grid->column('width', __('WIDTH'));
        $grid->column('url', __('URL'));
        $grid->column('created_at', __('CREATED AT'));
        $grid->column('updated_at', __('UPDATED AT'));

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

        $show->field('id', __('ID'));
        $show->field('xsh_note_id', __('XSH NOTE ID'));
        $show->field('x_id', __('X ID'));
        $show->field('height', __('HEIGHT'));
        $show->field('width', __('WIDTH'));
        $show->field('url', __('URL'));
        $show->field('created_at', __('CREATED AT'));
        $show->field('updated_at', __('UPDATED AT'));

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

        $form->number('id', __('ID'));
        $form->number('xsh_note_id', __('XSH NOTE ID'));
        $form->text('x_id', __('X ID'));
        $form->number('height', __('HEIGHT'));
        $form->number('width', __('WIDTH'));
        $form->url('url', __('URL'));
        $form->text('created_at', __('CREATED AT'));
        $form->text('updated_at', __('UPDATED AT'));

        return $form;
    }
}
