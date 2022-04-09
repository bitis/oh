<?php

namespace App\Admin\Controllers\Xhs;

use App\Models\XhsImage;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ImageController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'XhsImage';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new XhsImage());

        $grid->column('id', __('ID'));
        $grid->column('xsh_note_id', __('XSH NOTE ID'));
        $grid->column('fileid', __('FILEID'));
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
        $show = new Show(XhsImage::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('xsh_note_id', __('XSH NOTE ID'));
        $show->field('fileid', __('FILEID'));
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
        $form = new Form(new XhsImage());

        $form->number('ID', __('ID'));
        $form->number('XSH_NOTE_ID', __('XSH NOTE ID'));
        $form->text('FILEID', __('FILEID'));
        $form->number('HEIGHT', __('HEIGHT'));
        $form->number('WIDTH', __('WIDTH'));
        $form->url('URL', __('URL'));
        $form->text('CREATED_AT', __('CREATED AT'));
        $form->text('UPDATED_AT', __('UPDATED AT'));

        return $form;
    }
}
