<?php

namespace App\Admin\Controllers\Xhs;

use App\Models\XhsNote;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class NoteController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'XhsNote';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new XhsNote());

        $grid->column('id', __('ID'));
        $grid->column('x_id', __('X ID'));
        $grid->column('title', __('TITLE'));
        $grid->column('desc', __('DESC'));
        $grid->column('isLiked', __('ISLIKED'));
        $grid->column('type', __('TYPE'));
        $grid->column('time', __('TIME'));
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
        $show = new Show(XhsNote::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('x_id', __('X ID'));
        $show->field('title', __('TITLE'));
        $show->field('desc', __('DESC'));
        $show->field('isLiked', __('ISLIKED'));
        $show->field('type', __('TYPE'));
        $show->field('time', __('TIME'));
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
        $form = new Form(new XhsNote());

        $form->number('id', __('ID'));
        $form->text('x_id', __('X ID'));
        $form->text('title', __('TITLE'));
        $form->textarea('desc', __('DESC'));
        $form->text('isLiked', __('ISLIKED'));
        $form->text('type', __('TYPE'));
        $form->text('time', __('TIME'));
        $form->text('created_at', __('CREATED AT'));
        $form->text('updated_at', __('UPDATED AT'));

        return $form;
    }
}
