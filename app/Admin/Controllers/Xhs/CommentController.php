<?php

namespace App\Admin\Controllers\Xhs;

use App\Models\XhsComment;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CommentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'XhsComment';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new XhsComment());

        $grid->column('id', __('ID'));
        $grid->column('x_id', __('X ID'));
        $grid->column('parent_id', __('PARENT ID'));
        $grid->column('nickname', __('NICKNAME'));
        $grid->column('user_id', __('USER ID'));
        $grid->column('isSubComment', __('ISSUBCOMMENT'));
        $grid->column('content', __('CONTENT'));
        $grid->column('likes', __('LIKES'));
        $grid->column('isLiked', __('ISLIKED'));
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
        $show = new Show(XhsComment::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('x_id', __('X ID'));
        $show->field('parent_id', __('PARENT ID'));
        $show->field('nickname', __('NICKNAME'));
        $show->field('user_id', __('USER ID'));
        $show->field('isSubComment', __('ISSUBCOMMENT'));
        $show->field('content', __('CONTENT'));
        $show->field('likes', __('LIKES'));
        $show->field('isLiked', __('ISLIKED'));
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
        $form = new Form(new XhsComment());

        $form->number('id', __('ID'));
        $form->text('x_id', __('X ID'));
        $form->number('parent_id', __('PARENT ID'));
        $form->text('nickname', __('NICKNAME'));
        $form->text('user_id', __('USER ID'));
        $form->text('isSubComment', __('ISSUBCOMMENT'));
        $form->textarea('content', __('CONTENT'));
        $form->number('likes', __('LIKES'));
        $form->text('isLiked', __('ISLIKED'));
        $form->text('time', __('TIME'));
        $form->text('created_at', __('CREATED AT'));
        $form->text('updated_at', __('UPDATED AT'));

        return $form;
    }
}
